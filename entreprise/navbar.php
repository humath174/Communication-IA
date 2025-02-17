<nav class="bg-white shadow-md">
    <div class="max-w-screen-xl mx-auto p-4 flex justify-between items-center">
        <a href="/" class="text-xl font-semibold text-blue-600">Dashboard</a>
        <ul class="hidden md:flex space-x-6 text-gray-600">
            <li><a href="/index.php" class="text-blue-600">Dashboard</a></li>
            <li><a href="/boitemail/index.php">Email</a></li>
            <li><a href="/prompt/index.php">Prompt</a></li>
            <li><a href="/gestionenvoie/index.php">Envoie Mail</a></li>
            <li><a href="/activite/index.php">Activité</a></li>
        </ul>

        <!-- Profile Dropdown -->
        <div class="hidden md:flex items-center space-x-4">
            <button type="button" class="relative rounded-full bg-gray-800 p-1 text-gray-400 hover:text-white focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-800 focus:outline-hidden">
                <span class="absolute -inset-1.5"></span>
                <span class="sr-only">View notifications</span>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                </svg>
            </button>

            <div class="relative group">
                <!-- Bouton Profil -->
                <button type="button" class="relative flex rounded-full bg-gray-800 text-sm focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-800">
                    <span class="sr-only">Open user menu</span>
                    <img class="w-8 h-8 rounded-full" src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="">
                </button>

                <!-- Menu déroulant (affiché au survol) -->
                <div class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 ring-1 shadow-lg ring-black/5 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-opacity duration-200">
                    <a href="/entreprise/company.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Entreprise</a>
                    <a href="/account.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Paramètre</a>
                    <a href="/deconnexion.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Déconnexion</a>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Button -->
        <div class="md:hidden flex items-center">
            <button type="button" class="text-gray-600 focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>
    </div>

    <!-- Mobile Menu Script -->
    <script>
        const mobileMenuButton = document.querySelector("button[type='button']");
        const mobileMenu = document.querySelector("ul");

        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle("hidden");
        });
    </script>
</nav>
