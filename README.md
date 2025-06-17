# Socraites - AI-Powered Code Review Tool for PHP


An intelligent CLI tool that performs AI-assisted code reviews by analyzing your Git staged changes and their context.

## Features

- 🔍 **Smart Context Analysis**: Goes beyond simple `git diff` to understand code relationships
- 🤖 **AI-Powered Insights**: Leverages OpenAI to provide meaningful code review feedback
- ⚙️ **Configurable**: Adjust scoring weights and context size to fit your needs
- 📊 **Structured Output**: Beautifully formatted review results in your terminal
- 🧠 **Framework Aware**: Optional framework-specific analysis (via `--framework` flag)

## Installation

```bash
composer require drahil/socraites
```

## Usage

### Setup Socraites

Before using Socraites, run the interactive setup command to configure your environment:
```bash
vendor/bin/socraites setup
```

### Basic Code Code Review
```bash
vendor/bin/socraites code-review
```

### Options for Code Review
- `--framework=<framework>`: Specify a framework for tailored analysis (e.g., `laravel`, `symfony`, etc.)
- `--verbose-output`: Enable verbose output for detailed logs

## Configuration

Socraites supports multiple configuration methods, with the following priority order (highest to lowest):

1. `.socraites.json` file in the project root (created via `setup` command)
2. Laravel-style configuration (`config/socraites.php`)
3. Environment variables



### Required Configurations
```bash
export SOCRAITES_OPENAI_API_KEY=your_api_key_here
```

## How It Works

Socraites performs intelligent code analysis by:

- Collecting Changes:

  - Gets staged changes using git diff --staged

  - Identifies all modified files

- Building Context:

  - Analyzes relationships between changed files

  - Examines imports, extensions, and other code patterns

  - Respects configured weights for different patterns

- Generating Review:

  - Sends structured context to AI service

  - Returns formatted review with actionable insights

## Example Output

```bash
Analyzing your code...

"Wisdom begins in wonder."
- Socrates

  
  SOCRAITES CODE REVIEW


  ✅ Overall Summary:
  ────────────────────────────────────────────────────────────
     The changes in 'src/Entity/User.php' aim to define a new User class that implements the UserInterface from Symfony's Security component. However, the methods required by the interface are declared but not implemented, which will lead to issues during runtime if the class is used.
  ────────────────────────────────────────────────────────────


  📁 Files from context:
  ────────────────────────────────────────────────────────────
     src/Entity/User.php
  ────────────────────────────────────────────────────────────


  🔍 Reviewing: src/Entity/User.php
  ────────────────────────────────────────────────────────────
  ✅ Summary:
     Implementation of the User class which implements the UserInterface from Symfony Security.
  ❌ Major Issues:
     Unimplemented methods required by the UserInterface.
  ⚠️  Minor Issues:
     Missing newline at end of file.
  💡 Suggestions:
     Implement the methods 'getRoles', 'eraseCredentials', and 'getUserIdentifier' to fulfill the contract of the UserInterface.
     Add a newline at the end of the file to follow good coding standards.
  ────────────────────────────────────────────────────────────

  💬 Suggested Commit Message:
  ────────────────────────────────────────────────────────────
     Add User class skeleton implementing UserInterface
  ────────────────────────────────────────────────────────────
```

### 📦 PHP Dependencies

*(Automatically installed via Composer)*
- `PHP` ^8.0
- `nikic/php-parser` ^5.4 *(Code analysis)*
- `symfony/console` ^7.2 *(CLI interface)*
- `symfony/process` ^7.2 *(Git command execution)*
- `guzzlehttp/guzzle` ^7.0 *(OpenAI API communication)*

## 📃 License

Socrates is open-sourced software licensed under the [MIT license](LICENSE).

