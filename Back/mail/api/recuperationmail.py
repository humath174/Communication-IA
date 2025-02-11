import imaplib
import email
from email.header import decode_header
import mysql.connector
from datetime import datetime
import time
import chardet
import re

# Configuration de la base de données
DB_CONFIG = {
    "host": "192.168.1.200",
    "user": "grafana",
    "password": "grafana",
    "database": "botscommunication"
}

def connect_to_database():
    """Connexion à la base de données."""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        return conn
    except mysql.connector.Error as err:
        print(f"Erreur de connexion à la base de données : {err}")
        return None


def get_email_accounts_from_db():
    """Récupère la liste des comptes e-mail depuis la base de données."""
    conn = connect_to_database()
    if not conn:
        return []

    cursor = conn.cursor(dictionary=True)
    try:
        query = "SELECT id, imap_server, email_address, password, company_id FROM email_accounts WHERE is_active = TRUE"
        cursor.execute(query)
        accounts = cursor.fetchall()
        return accounts
    except mysql.connector.Error as err:
        print(f"Erreur lors de la récupération des comptes e-mail : {err}")
        return []
    finally:
        cursor.close()
        conn.close()


def insert_email_into_db(conn, to, sender, subject, message, timestamp):
    """Insertion d'un e-mail dans la table bdd_insertion."""
    cursor = conn.cursor()
    try:
        query = """
        INSERT INTO bdd_insertion (`to`, `from`, `subject`, `message`, `timestamp`)
        VALUES (%s, %s, %s, %s, %s)
        """
        cursor.execute(query, (to, sender, subject, message, timestamp))
        conn.commit()
    except mysql.connector.Error as err:
        print(f"Erreur lors de l'insertion des données : {err}")
    finally:
        cursor.close()


def fetch_emails_for_account(imap_server, email_account, password, company_id):
    """Récupère les e-mails non lus pour un compte donné."""
    try:
        # Connexion au serveur IMAP
        mail = imaplib.IMAP4_SSL(imap_server)
        mail.login(email_account, password)
        mail.select("inbox")

        # Recherche des e-mails non lus
        status, messages = mail.search(None, "UNSEEN")
        if status != "OK":
            print(f"Impossible de récupérer les e-mails pour {email_account}.")
            return

        email_ids = messages[0].split()

        # Connexion à la base de données
        conn = connect_to_database()
        if not conn:
            return

        for email_id in email_ids:
            # Récupérer l'e-mail brut
            status, msg_data = mail.fetch(email_id, "(RFC822)")
            if status != "OK":
                print(f"Impossible de récupérer l'e-mail {email_id} pour {email_account}.")
                continue

            for response_part in msg_data:
                if isinstance(response_part, tuple):
                    # Parse l'e-mail
                    msg = email.message_from_bytes(response_part[1])

                    # Décode les champs principaux
                    raw_sender = decode_email_header(msg["From"])
                    raw_to = decode_email_header(msg["To"])
                    subject = clean_text(decode_email_header(msg["Subject"]))
                    date = msg["Date"]

                    # Extraire uniquement l'adresse e-mail
                    sender = extract_email(raw_sender)
                    to = extract_email(raw_to)

                    # Convertir la date en format datetime
                    timestamp = datetime.strptime(date, "%a, %d %b %Y %H:%M:%S %z").strftime("%Y-%m-%d %H:%M:%S")

                    # Décoder le contenu du message
                    message = ""
                    if msg.is_multipart():
                        for part in msg.walk():
                            content_type = part.get_content_type()
                            if content_type == "text/plain":  # On récupère uniquement le texte brut
                                payload = part.get_payload(decode=True)
                                if payload:
                                    message = decode_email_body(payload)
                                    break
                    else:
                        payload = msg.get_payload(decode=True)
                        if payload:
                            message = decode_email_body(payload)

                    # Insérer l'e-mail dans la base de données
                    insert_email_into_db(conn, to, sender, subject, message, timestamp)

        # Déconnexion
        conn.close()
        mail.logout()
    except Exception as e:
        print(f"Erreur lors de la récupération des e-mails pour {email_account} : {e}")


if __name__ == "__main__":
    while True:
        # Récupérer les comptes e-mail depuis la base de données
        email_accounts = get_email_accounts_from_db()

        # Vérifier les e-mails pour chaque compte
        for account in email_accounts:
            imap_server = account["imap_server"]
            email_account = account["email_address"]
            password = account["password"]
            company_id = account["company_id"]

            print(f"Vérification des e-mails pour {email_account}...")
            fetch_emails_for_account(imap_server, email_account, password, company_id)

        # Attendre avant de vérifier à nouveau
        time.sleep(1)  # Attendre 1 minute
