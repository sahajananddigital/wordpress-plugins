# AI Site Generator (Local & Free)

A high-performance WordPress plugin that generates complete, beautiful websites using local AI reasoning and native block patterns.

## 🎯 Vision
This plugin is designed to be **100% affordable** for developers. It eliminates all cloud API dependencies (like OpenAI, HuggingFace, or Pexels) by running the AI reasoning locally on your machine and using professionally designed native WordPress patterns for the layout.

## 🛠 Tech Stack
- **AI Engine:** Local LLM via [Ollama](https://ollama.com/) (OpenAI-compatible).
- **Default Model:** `llama3:8b` (Optimized for Apple Silicon / M2 Pro).
- **Design System:** [Ollie Theme](https://olliewp.com/) Block Patterns.
- **Development:** WordPress Playground CLI.
- **Frontend:** React (@wordpress/scripts).

## 🚀 Getting Started

### 1. Install local AI (Ollama)
Ensure Ollama is installed and the model is pulled:
```bash
ollama pull llama3:8b
```

### 2. Launch Development Environment
Run the following command to build the assets and start a clean WordPress instance in your browser:
```bash
npm run playground
```
This will automatically:
- Install and activate the **Ollie Theme**.
- Activate this plugin.
- Configure the local Ollama endpoint.
- Open the AI Site Generator dashboard.

## 🧪 Testing & Development
- **Run Unit Tests:** `npm test` (Uses Playground's PHP environment).
- **Build Assets:** `npm run build` (Compiles the React UI).

## 📜 Key Features
- **Strategy-Only AI:** The LLM acts as a "Site Architect", planning pages and sections.
- **Visual Preview:** Review and edit generated content in a high-fidelity mockup before finalizing.
- **Strict Services:** AI strictly follows your provided list of services.
- **Zero Cost:** No API keys required. No monthly subscriptions.
