import openai
import mysql.connector
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
import time
import os


# Configuration de l'API OpenAI
openai.api_key = os.getenv("OPENAI_API_KEY")

# Configuration de la base de données
DB_CONFIG = {
    "host": "192.168.1.24",
    "user": "grafana",
    "password": "grafana",
    "database": "botscommunication"
}

# Configuration de l'envoi d'email
SMTP_SERVER = "smtp.mailo.com"  # Exemple pour Mailo, modifie selon ton fournisseur
SMTP_PORT = 587
EMAIL_ACCOUNT = "ai@digitalweb-dynamics.com"
PASSWORD = "AIprojectvoix"

def connect_to_database():
    """Connexion à la base de données."""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        return conn
    except mysql.connector.Error as err:
        print(f"Erreur de connexion à la base de données : {err}")
        return None

def get_prompt_for_email(conn, email_to):
    """
    Récupère le prompt spécifique au destinataire de l'email depuis la table Prompt_Email.
    """
    cursor = conn.cursor(dictionary=True)
    try:
        query = "SELECT prompt FROM Prompt_Email WHERE email = %s"
        cursor.execute(query, (email_to,))
        result = cursor.fetchone()
        return result["prompt"] if result else None
    except mysql.connector.Error as err:
        print(f"Erreur lors de la récupération du prompt : {err}")
        return None
    finally:
        cursor.close()

def generate_reply_with_chatgpt(prompt, email_subject, email_message):
    """
    Utilise ChatGPT pour générer une réponse en fonction du prompt et de l'e-mail reçu.
    """
    messages = [
        {"role": "system", "content": prompt},
        {"role": "user", "content": f"Sujet : {email_subject}\nMessage : {email_message}"}
    ]
    try:
        response = openai.ChatCompletion.create(
            model="gpt-3.5-turbo",  # Modèle GPT-3.5
            messages=messages,
            max_tokens=500,
            temperature=0.7
        )
        return response.choices[0].message['content'].strip()
    except Exception as e:
        print(f"Erreur avec l'API OpenAI : {e}")
        return "Je n'ai pas pu générer une réponse pour le moment."

def send_email(to, subject, message):
    """Envoie l'e-mail de réponse générée."""
    try:
        msg = MIMEMultipart()
        msg["From"] = EMAIL_ACCOUNT
        msg["To"] = to
        msg["Subject"] = "Re: " + subject

        body = MIMEText(message, "plain")
        msg.attach(body)

        # Connexion au serveur SMTP et envoi de l'email
        with smtplib.SMTP(SMTP_SERVER, SMTP_PORT) as server:
            server.starttls()
            server.login(EMAIL_ACCOUNT, PASSWORD)
            server.sendmail(EMAIL_ACCOUNT, to, msg.as_string())
            print(f"Réponse envoyée à {to}")
    except Exception as e:
        print(f"Erreur lors de l'envoi de l'e-mail : {e}")

def process_responses():
    """
    Traite les e-mails dans la base de données et génère des réponses avec ChatGPT.
    """
    conn = connect_to_database()
    if not conn:
        return

    cursor = conn.cursor(dictionary=True)

    try:
        # Récupérer les e-mails non encore traités
        cursor.execute("SELECT * FROM bdd_reponse WHERE id NOT IN (SELECT email_id FROM actions_reponse);")
        emails = cursor.fetchall()

        for email in emails:
            email_id = email["id"]
            email_to = email["to"]
            email_from = email["from"]
            email_subject = email["subject"]
            email_message = email["message"]

            # Récupérer le prompt spécifique au destinataire
            prompt = get_prompt_for_email(conn, email_to)
            if not prompt:
                print(f"Aucun prompt trouvé pour l'adresse {email_to}.")
                continue

            # Générer la réponse avec ChatGPT
            reply = generate_reply_with_chatgpt(prompt, email_subject, email_message)

            # Sauvegarder la réponse dans la base de données
            save_reply_to_database(conn, email_id, email_to, email_from, email_subject, email_message, reply)

            # Envoyer la réponse par e-mail
            send_email(email_from, email_subject, reply)

    except mysql.connector.Error as err:
        print(f"Erreur lors du traitement des réponses : {err}")
    finally:
        cursor.close()
        conn.close()

def save_reply_to_database(conn, email_id, email_to, email_from, email_subject, email_message, reply):
    """
    Sauvegarde la réponse générée dans une table de suivi des actions.
    """
    cursor = conn.cursor()
    try:
        query = """
        INSERT INTO actions_reponse (email_id, email_to, email_from, subject, original_message, reply_message, action_timestamp)
        VALUES (%s, %s, %s, %s, %s, %s, NOW())
        """
        cursor.execute(query, (email_id, email_to, email_from, email_subject, email_message, reply))
        conn.commit()
        print(f"Réponse sauvegardée pour l'e-mail ID {email_id}.")
    except mysql.connector.Error as err:
        print(f"Erreur lors de la sauvegarde de la réponse : {err}")
    finally:
        cursor.close()

if __name__ == "__main__":
    while True:
        process_responses()
        time.sleep(1)  # Attendre 5 secondes avant de vérifier à nouveau
