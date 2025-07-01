document.addEventListener('DOMContentLoaded', function () {
        const themeToggle = document.getElementById('themeToggle');
        const currentTheme = localStorage.getItem('theme') || 'light';
        const navbar = document.getElementById('navbar');

        if (currentTheme === 'dark') {
            document.body.classList.add('bg-dark', 'text-light');
            document.body.classList.remove('bg-light', 'text-dark');
            navbar.classList.add('navbar-dark', 'bg-dark');
            navbar.classList.remove('navbar-light', 'bg-light');
	    themeToggle.innerHTML = '<i class="bi bi-brightness-high"></i> Light';
            document.querySelectorAll('table').forEach(table => {
                table.classList.add('table-dark');
                table.classList.remove('table-light');
            });
        } else {
            document.body.classList.add('bg-light', 'text-dark');
            document.body.classList.remove('bg-dark', 'text-light');
            navbar.classList.add('navbar-light', 'bg-light');
            navbar.classList.remove('navbar-dark', 'bg-dark');
	    themeToggle.innerHTML = '<i class="bi bi-moon-stars"></i> Dark';
            document.querySelectorAll('table').forEach(table => {
                table.classList.add('table-light');
                table.classList.remove('table-dark');
            });
        }

        themeToggle.addEventListener('click', function () {
            document.body.classList.toggle('bg-dark');
            document.body.classList.toggle('text-light');
            document.body.classList.toggle('bg-light');
            document.body.classList.toggle('text-dark');

            navbar.classList.toggle('navbar-dark');
            navbar.classList.toggle('bg-dark');
            navbar.classList.toggle('navbar-light');
            navbar.classList.toggle('bg-light');

            let theme = 'light';
            if (document.body.classList.contains('bg-dark')) {
                theme = 'dark';
                themeToggle.innerHTML = '<i class="bi bi-brightness-high"></i> Light';
            } else {
            	themeToggle.innerHTML = '<i class="bi bi-moon-stars"></i> Dark';
	    }

            localStorage.setItem('theme', theme);

            document.querySelectorAll('table').forEach(table => {
                if (theme === 'dark') {
                    table.classList.add('table-dark');
                    table.classList.remove('table-light');
                } else {
                    table.classList.add('table-light');
                    table.classList.remove('table-dark');
                }
            });

            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                if (theme === 'dark') {
                    menu.classList.add('bg-dark');
                    menu.classList.remove('bg-light');
                } else {
                    menu.classList.add('bg-light');
                    menu.classList.remove('bg-dark');
                }
            });
        });
    });
document.addEventListener('click', function (event) {
    const alerts = document.querySelectorAll('.alert');

    alerts.forEach((alertElement) => {
        const isClickInsideAlert = alertElement.contains(event.target);

        if (!isClickInsideAlert) {
            const alert = bootstrap.Alert.getOrCreateInstance(alertElement);
            alert.close();
        }
    });
});
