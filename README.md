# dashapi – Clouding API Dashboard (BETA)

dashapi is a web-based dashboard for managing and interacting with the [Clouding.io](https://clouding.io) API.  
It provides a clean, centralized interface to work with multiple Clouding accounts, and includes advanced features such as impersonation and internal activity logging.

## 🔧 Features

- 🔐 **Multi-account support**: Easily manage multiple Clouding accounts from one panel.
- 🧑‍💼 **Superadmin impersonation**: Superadmins can impersonate any added account and act on their behalf.
- 📊 **Full API coverage**: All functions available via the Clouding API are accessible through the dashboard.
- 📜 **Internal panel logs**: All actions within the dashboard are logged for audit and traceability.
- 🌙 **Light/Dark mode** – Switch between light and dark themes seamlessly.

## 🚀 Getting Started

1. Clone the repository: 
<pre>git clone https://github.com/usernamestringnull/dashapi.git /var/www/html && chown www-data:www-data /var/www/html -R</pre>
2. Configure your environment and API keys.
3. Run the panel locally or deploy it on your preferred hosting.

## 🛡️ Requirements

- PHP 8.x or higher
- Web server NGINX
- MySQL or MariaDB

## 📚 Documentation

Check the full API documentation at:  
🔗 https://docs.clouding.io

For advanced configuration, impersonation setup, and user role management, refer to the `docs/` folder in the repository.

## 🤝 Contributing

This panel was originally built by the community. Contributions are welcome — feel free to submit pull requests or open issues.

## 📜 License

GNU License

---

If you have any questions or feature requests, please contact the maintainers or open a GitHub issue.

Happy hosting! 🚀
