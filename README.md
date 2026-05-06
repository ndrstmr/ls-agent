# LS-KI Agent (PoC)

Kleine Symfony-Webapp, die Verwaltungstexte (Beamtendeutsch) per
[Steg](https://github.com/ndrstmr/steg) an ein OpenAI-kompatibles LLM
schickt und in **Leichte Sprache** zurückübersetzt.

PoC für die Lotse-Plattform — minimaler Showcase für
`ndrstmr/steg` + `ndrstmr/steg-bundle`.

## Stack

- PHP 8.4, Symfony 7.4
- `ndrstmr/steg` ^1.0 + `ndrstmr/steg-bundle` ^0.1
- Inferenz: vLLM (DSN-konfiguriert)

## Setup

```bash
# Abhängigkeiten installieren
composer install

# Lokale Konfiguration anlegen
cp .env .env.local
```

`.env.local` editieren und den vLLM-Endpoint eintragen:

```dotenv
STEG_VLLM_DSN=vllm://vllm.example.org:8000/v1?model=llama-3.3-70b-awq
```

## Starten

```bash
# Symfony CLI
symfony server:start

# oder PHP-eingebauter Server
php -S 127.0.0.1:8000 -t public/
```

Öffnen: <http://127.0.0.1:8000>

## Prompts

Prompt-Texte liegen versioniert unter `config/prompts/` und werden zur
Laufzeit über den `PromptLoader` geladen. Aktive Version steuert
`PROMPT_VERSION` (Default: `v1.0`).

```
config/prompts/v1.0/translate.txt    # System-Prompt für die Übersetzung
```

Neue Version: `config/prompts/v1.1/...` anlegen, `PROMPT_VERSION=v1.1`
in `.env.local` setzen — kein Neudeployment des Codes nötig.

## Tests

```bash
vendor/bin/phpunit
```

Die Tests nutzen `Steg\Client\MockClient`, kein laufender Inferenz-Server
nötig.

## Aufbau

```
src/
├── Controller/TranslateController.php
├── Form/TranslateFormType.php
└── Translator/
    ├── LeichteSpracheTranslator.php   # Service: Steg-Aufruf + Prompt
    ├── PromptLoader.php               # Lädt config/prompts/{version}/{name}.txt
    ├── TranslationRequest.php         # Eingabe-VO
    └── TranslationResult.php          # Ergebnis-VO
config/
├── packages/steg.yaml                 # Bundle-Connection
└── prompts/v1.0/translate.txt         # System-Prompt
```
