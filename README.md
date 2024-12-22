# AI Chat Application

This is a PHP-based chat application that integrates with AI models to provide interactive conversations.

## Features

- Interactive chat interface
- AI model integration
- Chat history tracking
- Multiple model support
- Database storage for conversations

## Requirements

- PHP 7.4 or higher
- MySQL/MariaDB
- Web server (Apache/Nginx)

## Installation

1. Clone the repository
```bash
git clone [your-repository-url]
```

2. Copy the example configuration file
```bash
cp config.example.php config.php
```

3. Update the configuration file with your database credentials and API keys

4. Initialize the database
```bash
php init_db.php
```

5. Configure your web server to point to the project directory

## Configuration

The application requires several configuration parameters to be set in `config.php`:

- Database connection details
- API credentials
- Application settings

For security reasons, the actual `config.php` file is not included in the repository. Use `config.example.php` as a template.

## Usage

1. Access the application through your web browser
2. Select an AI model from the available options
3. Start chatting!

## Security

Make sure to:
- Keep your `config.php` file secure and never commit it to version control
- Regularly update your dependencies
- Use secure database credentials
- Protect your API keys

## License

MIT License - See [LICENSE](LICENSE) file for details
