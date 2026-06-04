# Gemini CLI Project Context: AI Site Generator

## 🎯 Project Vision
This is a high-performance, **zero-cost** WordPress site generator. It uses local AI to architect site structures and native WordPress Block Patterns (optimized for the **Ollie Theme**) to assemble beautiful layouts.

## 🛠 Tech Stack & Architecture
- **AI Engine:** Local LLM via **Ollama** (OpenAI-compatible endpoint).
- **Default Model:** `llama3:8b` (Optimized for M2 Pro / 16GB RAM).
- **Patterns:** Native WordPress Patterns + **Ollie Theme** slugs.
- **Development:** WordPress Playground CLI (`wp-playground-cli`).
- **Frontend:** React + **Visual Mockup Preview**.

## 📜 Foundational Mandates (Project Rules)
1. **No Cloud API Dependencies:** Do NOT re-introduce cloud LLM or image APIs.
2. **Architecture-Only AI:** The LLM should only generate the JSON strategy and high-fidelity copy.
3. **Service Strictness:** The AI must strictly use the user-provided "Services List" and not invent new ones.
4. **Ollie-First Design:** All new section mappings in `Content_Manager` must prioritize `ollie/*` patterns.
5. **Visual Review Step:** The generator must always provide a "Review" screen with a "Visual Preview" toggle before finalizing the site.

## 🚀 Key Commands
- `npm run playground`: Launches a fresh, pre-configured WP instance with Ollie Theme and the plugin activated.
- `npm test`: Runs the PHP unit testing suite via Playground's PHP environment.
- `npm run build`: Compiles the React generator UI.

## 📁 Critical Files
- `includes/class-mcp-client.php`: Local LLM connection logic.
- `includes/class-content-manager.php`: Pattern mapping and page creation.
- `blueprint.json`: Playground configuration.
