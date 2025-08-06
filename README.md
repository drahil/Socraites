# Socraites – AI-Powered Code Review Tool for Laravel

An intelligent Laravel-specific CLI tool that performs AI-assisted code reviews by analyzing your staged Git changes and their surrounding code context.

> ⚠️ For development environments only<br>
> 📦 PostgreSQL is required<br>
> 🧱 Laravel-only support

## ✨ Features

    🧠 AI-Powered Reviews: Get actionable, context-aware code feedback using OpenAI

    🛠️ Built for Laravel: Tailored to Laravel conventions and structure

    📁 Function-Level Chunking: Your codebase is automatically broken down and vectorized by method/function

    🔍 Persistent Vector Context: Code context is stored in PostgreSQL for efficient retrieval

    ⚙️ Fully Configurable: Customize OpenAI model, temperature, max context size, etc.

    ✅ Simple Artisan Workflow: Setup and review your codebase with familiar Artisan commands

## 📦 Installation

Install the package in development only:
```bash
composer require --dev drahil/socraites
```
Publish the config and migration files:
```bash
php artisan vendor:publish --provider="drahil\Socraites\Providers\SocraitesServiceProvider"
```
Run the migration to create the code_chunks table (PostgreSQL only):
```bash
php artisan migrate
```
## 🚀 Usage
1. Setup Socraites

    This interactive command configures your environment and generates .socraites.json:
    ```bash
    php artisan socraites:setup
    ```
    It collects and stores:
    ```json
    {
      "maximum_context_size": 10000,
      "ignore_patterns": ["tests/", "vendor/"],
      "extensions": [".php"],
      "openai_model": "gpt-4",
      "openai_temperature": 0.2,
      "question_prompt": "Perform a detailed code review"
    }
    ```

2. Vectorize Your Codebase

   This command chunks your codebase by functions/methods and stores vectors in the `code_chunks` table:
    ```bash
    php artisan socraites:vectorize
    ```
3. Perform Code Review

   After staging your changes via git add, run the review:
   Analyze your staged changes and surrounding context:
    ```bash
    php artisan socraites:code-review
    ```


## 🧾 Example Output

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
   Do you have a question about the code review? [no]:
   > 
```
After the code review output, Socraites offers an optional interactive prompt.

You can type a custom question (e.g., "*Why is this considered a major issue?*" or "*How can I improve this method further?*"), and Socraites will generate a detailed AI response based on the review context.

### 📦 PHP Dependencies

*(Automatically installed via Composer)*
- `PHP` ^8.0
- `nikic/php-parser` ^5.4 *(Code analysis)*
- `symfony/console` ^7.2 *(CLI interface)*
- `symfony/process` ^7.2 *(Git command execution)*
- `guzzlehttp/guzzle` ^7.0 *(OpenAI API communication)*

## 📃 License

Socraites is open-sourced software licensed under the [MIT license](LICENSE).

