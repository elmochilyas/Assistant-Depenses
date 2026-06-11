# 📋 Project Brief — Assistant Dépenses

> **Durée :** 5 jours · Lundi 08/06/2026 (10h00) → Vendredi 12/06/2026 (14h30)
> **Mode :** Individuel
> **Stack :** Laravel · Groq API via `laravel/ai` · Queue Worker · Pest

---

## 🧭 Contexte

Si Brahim tient une épicerie de quartier. Chaque mois, il accumule des dizaines de reçus fournisseurs — papier griffonné, montants en darija, abréviations personnelles. Il n'a aucune visibilité sur ses dépenses par catégorie, et la saisie manuelle dans un tableur est hors de question.

**Le problème à résoudre :** transformer un reçu brut collé en texte en une liste de dépenses propre, structurée, classée par catégorie, consultable et suivie dans le temps — sans que l'utilisateur attende une page gelée.

**La solution :** une application Laravel qui dispatche l'extraction IA dans un Job asynchrone, garantit la structure du résultat via le structured output du SDK `laravel/ai`, et stocke des données typées de bout en bout.

---

## 🗂️ User Stories

### 🔐 Authentification

| ID | En tant que… | Je veux… | Pour… |
|----|--------------|----------|-------|
| US1 | Utilisateur | Créer mon compte, me connecter et me déconnecter | Que mes reçus me soient rattachés |

---

### 🧾 Gestion des Reçus

| ID | En tant que… | Je veux… | Pour… |
|----|--------------|----------|-------|
| US2 | Utilisateur connecté | Voir la liste de tous mes reçus avec statut (En attente / Traité / Échoué) et nombre de dépenses extraites | Suivre d'un coup d'œil l'état de chaque reçu |
| US3 | Utilisateur connecté | Coller le texte d'un reçu et lancer l'extraction, avec affichage immédiat de « Reçu en cours de traitement » | Que la page ne se fige jamais pendant que l'IA travaille |
| US4 | Utilisateur connecté | Ouvrir un reçu et voir : texte source, statut, liste des dépenses (libellé, quantité, prix unitaire, catégorie) | Consulter le détail de chaque extraction |
| US5 | Utilisateur connecté | Supprimer un reçu et ses dépenses associées | Gérer mon historique |

---

### 🤖 Extraction IA

| ID | En tant que… | Je veux… | Pour… |
|----|--------------|----------|-------|
| US6 | Utilisateur connecté | Que l'IA extraie les articles en **structured output garanti** (contrat JSON défini), validé et enregistré en base | Avoir une ligne de dépense typée par article, sans json_decode qui plante |
| US7 | Utilisateur connecté | Voir le statut évoluer (En attente → Traité), et en cas d'erreur (réponse hors schéma, API injoignable) voir **Échoué** clairement | Ne jamais tomber sur une page blanche |

---

### 📊 Suivi des Dépenses

| ID | En tant que… | Je veux… | Pour… |
|----|--------------|----------|-------|
| US8 | Utilisateur connecté | Voir toutes mes dépenses avec catégorie formatée et **filtrer par catégorie** | Suivre mes postes de dépense dans le temps |

---

### ⭐ Bonus

| Fonctionnalité | Détail |
|----------------|--------|
| **Entrée par image** | Uploader une photo de reçu (File Storage + modèle multimodal) |
| **Test Pest** | Test d'extraction avec le fake du SDK `laravel/ai` — sans appel Groq réel, rapide et déterministe |

---

## 📐 Contrat JSON (Structured Output)

```json
{
  "articles": [
    {
      "libellé": "string",
      "quantité": "integer",
      "prix_unitaire": "number",
      "catégorie": "enum: alimentaire | boissons | hygiène | entretien | autre"
    }
  ],
  "total_estimé": "number",
  "devise": "string (ex: MAD)"
}
```

> ⚠️ Ce contrat est **non-négociable**. Si Brahim a besoin que les données aient toujours la bonne forme. Le structured output garanti du SDK `laravel/ai` est là précisément pour ça.

---

## 🏗️ Architecture Backend — Concepts Exigés

### 1. Traitement Asynchrone (Queue + Job) — **Obligatoire**

**Pourquoi :** L'appel IA est lent. Sans queue, la page se fige le temps que Groq réponde. L'utilisateur ne doit jamais attendre.

```bash
php artisan make:job ExtraireDepensesDuRecu
php artisan queue:work
```

Le Job est dispatché immédiatement à la soumission du reçu. L'utilisateur voit le statut **En attente** puis **Traité** une fois le worker terminé.

---

### 2. Form Request — **Obligatoire**

**Pourquoi :** Valider le texte soumis (non-vide, longueur min/max) avant tout appel IA — donc avant de gaspiller un token API.

```bash
php artisan make:request StoreRecuRequest
```

---

### 3. Eloquent Casts — **Obligatoires**

**Pourquoi :** Les données restent typées de l'extraction jusqu'à l'affichage. Pas de string à parser à la main.

| Champ | Cast |
|-------|------|
| `statut` | `enum` (EnAttenteTraitéÉchoué) |
| `catégorie` | `enum` (alimentaire…) |
| `payload_brut` | `array` / `json` |

---

### 4. Relation Eloquent

```
Recu  1 ──────────── N  Depense
```

- `Recu::hasMany(Depense::class)`
- `Depense::belongsTo(Recu::class)`
- **Eager loading obligatoire** — zéro N+1 vérifié avec Laravel Debugbar

---

### 5. Appel IA via `laravel/ai`

**Pourquoi `laravel/ai` et pas `Http::` :** Le SDK fournit un structured output garanti avec validation de schéma intégrée. Un `Http::post()` brut peut retourner du JSON malformé — le `json_decode` plante en silence. Ici, si le contrat n'est pas respecté → exception catchée → statut **Échoué**, pas de crash silencieux.

---

## 🤝 Workflow AI-Assisted

### AGENTS.md
- Présent à la racine du projet
- **Premier commit du Jour 1**
- Contenu : rôle de l'agent, stack, contraintes, conventions de commit

### OpenSpec
- Outil : [openspec.dev](https://openspec.dev/)
- Structure : `proposal → specs → tasks`, versionnés dans le repo
- Dossier `specs/` avec **minimum 3 features documentées**
- Un fichier par feature

### Coding Agent
- **Recommandé :** OpenCode
- Mode : **Plan** avant **Build** pour chaque feature
- Chaque commit doit mentionner clairement l'usage AI

---

## 📁 Livrables

| Livrable | Détail |
|----------|--------|
| **Jira** | Board avec tickets par US |
| **MCD & MLD** | Schémas de base de données |
| **GitHub Repository** | Public, minimum 15 commits explicites |
| **Branches** | `feature/recus-crud` · `feature/extraction-ia` · `feature/queue-traitement` |
| **Commits quotidiens** | Obligatoires |
| **Dossier `specs/`** | Géré avec OpenSpec, commité et visible |
| **`README.md`** | Instructions d'installation et d'utilisation |

---

## ✅ Critères de Performance

### 🏛️ Architecture Laravel — 30%

- [ ] Relation `Recu hasMany Depense` définie et utilisée correctement
- [ ] `StoreRecuRequest` pour la validation avant l'appel IA
- [ ] Eloquent Casts fonctionnels (enum statut, enum catégorie, array payload brut)
- [ ] Extraction dispatchée dans un Job traité par un worker — page non bloquante
- [ ] Appel IA via `laravel/ai`, provider en config, résultat sauvegardé en base
- [ ] Zéro N+1 vérifié avec Debugbar

### ⚙️ Fonctionnalités — 25%

- [ ] Authentification complète (inscription / connexion / déconnexion)
- [ ] CRUD Reçus complet avec statut de traitement visible
- [ ] Extraction IA structurée fonctionnelle — contrat JSON respecté, dépenses typées sauvegardées
- [ ] Suivi du statut (En attente / Traité / Échoué) reflétant l'état réel
- [ ] Liste des dépenses filtrable par catégorie

### 🤖 Workflow AI-Assisted — 25%

- [ ] `AGENTS.md` présent, complet et commité au Jour 1
- [ ] Dossier `specs/` géré avec OpenSpec, ≥ 3 features documentées
- [ ] Commits avec mention claire de l'usage AI
- [ ] Capacité à expliquer ce que l'agent a généré vs ce qui a été modifié
- [ ] Capacité à expliquer **pourquoi** le structured output et la Queue sont là (décisions d'architecture)

### 🗃️ MCD & MLD — 20%

- [ ] Structure respectée avec MCD et MLD corrects

---

## ⚙️ Stack Technique

| Couche | Technologie |
|--------|-------------|
| Framework | Laravel (dernière version stable) |
| IA | Groq API via `laravel/ai` |
| Queue | Laravel Queue + Worker |
| Auth | Laravel Breeze |
| Tests | Pest |
| Debug | Laravel Debugbar |
| Specs | OpenSpec |
| Agent | OpenCode (recommandé) |

---

## 🧠 La Question Architecturale à Garder en Tête

> *À chaque concept de ce brief, demande-toi : quel problème réel est-ce que ça résout ? Si tu ne sais pas répondre, tu n'as pas compris le concept.*

| Concept | Problème résolu |
|---------|-----------------|
| **Queue + Job** | La page se figeait pendant que Groq réfléchissait |
| **Structured Output** | `json_decode` qui plante silencieusement sur une réponse malformée |
| **Form Request** | Gaspiller un appel API sur un texte vide ou invalide |
| **Eloquent Casts** | Des strings éparpillées à parser à la main partout dans le code |
| **Eager Loading** | N+1 queries qui explosent les perfs sur la liste des reçus |