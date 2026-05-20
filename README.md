# Module 08 — Symfony Blog App

A Symfony 7.4 blog application with Ajax forms, modal post viewing, real-time updates via WebSockets, and SQLite as the local database.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Symfony 7.4.* (LTS) |
| Language | PHP 8.4 |
| Database | SQLite (`var/app.db`) |
| Templating | Twig |
| Frontend | Vanilla JavaScript (Ajax + WebSocket) |
| WebSocket | Workerman 5.x |

---

## Project Setup

### 1. Create the Symfony project

```bash
composer create-project symfony/skeleton:"7.4.*" module_08
cd module_08
```

### 2. Install dependencies

```bash
composer require symfony/maker-bundle --dev
composer require symfony/orm-pack
composer require symfony/security-bundle
composer require symfony/form
composer require twig/twig
composer require symfony/twig-bundle
composer require symfony/asset
composer require symfony/validator
composer require workerman/workerman
```

---

## Running the app

```bash
# Build the image (first time or after Dockerfile changes)
make build

# Start the containers
make up

# Stop the containers
make down
```

The app will be available at `http://localhost:8000`.
Both the Symfony server and the WebSocket server (port 8080) start automatically inside the container.

---

## Exercises

### Exercise 00 — Post Entity

Create the `Post` entity with the following fields: `title`, `content`, and `created` (auto-set in the constructor).

```bash
php bin/console make:entity Post
```

Files involved:
- `src/Entity/Post.php`

**Flow:**
The `Post` entity maps to a `post` table in SQLite. The `created` field is automatically set to the current date and time in the constructor, so the caller never has to set it manually.

---

### Exercise 01 — Authentication & Base Structure

Set up user authentication, the post form, controllers, templates, and the database.

**1. Create the User entity**
```bash
php bin/console make:user
```

**2. Configure security**

Edit `config/packages/security.yaml`:
- Provider: `User` entity identified by `email`
- Firewall: `json_login` with `username_path: username`
- Logout redirects to `app_default`
- Access control: `/post/new` requires `ROLE_USER`

**3. Create the login handlers**
- `src/Security/LoginSuccessHandler.php` — returns `{ success: true }`
- `src/Security/LoginFailureHandler.php` — returns `{ success: false }`

**4. Create the Post form**
```bash
php bin/console make:form PostType Post
```

**5. Create the controllers**
```bash
php bin/console make:controller UserController
php bin/console make:controller PostController
```

**6. Create the templates**
- `templates/user/login.html.twig` — Ajax login form (standalone page)
- `templates/post/index.html.twig` — main page (post form + posts list)
- `templates/base.html.twig` — main layout with logout button and toast system

**7. Set up the database**
```bash
php bin/console doctrine:database:create
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

**8. Create the command to add users**
```bash
php bin/console make:command app:create-user
```
File: `src/Command/CreateUserCommand.php`

**9. Add a user**
```bash
php bin/console app:create-user
```

**Flow:**

```
User visits /
  └─ PostController::defaultAction checks getUser()
       ├─ Not logged in → redirect to /login
       └─ Logged in     → render post form + posts list

User visits /login
  └─ UserController::loginAction checks getUser()
       ├─ Already logged in → redirect to /
       └─ Not logged in     → render login form

User submits login form (Ajax)
  └─ fetch POST /login (JSON) → Symfony json_login firewall
       ├─ Credentials valid   → LoginSuccessHandler returns { success: true }
       │                         JS redirects to /
       └─ Credentials invalid → LoginFailureHandler returns { success: false }
                                 JS shows error toast

User clicks logout button
  └─ GET /logout → Symfony clears session → redirects to /
                   → / detects no user → redirects to /login
```

The login form is submitted via Ajax as JSON. Symfony's `json_login` firewall intercepts the request on `/login`, verifies the credentials, and calls the appropriate handler. No page reload occurs on failure — the error is shown as a toast. On success, JavaScript redirects to `/` which now shows the post form.

---

### Exercise 02 — Posts List & Validation

Wire up post creation with Ajax, display the list, and add validation.

- `PostController::defaultAction` — fetches all posts sorted by date (DESC) and passes them to the template
- `PostController::newAction` — handles Ajax form submission, returns JSON, collects and returns form errors
- `Post` entity — add `UniqueEntity` constraint on `title`
- `templates/post/index.html.twig` — display the posts list, handle Ajax form submission, show success/error toasts
- `templates/base.html.twig` — add the toast system and the logout button (visible only when logged in)

**Flow:**

```
User fills in the post form and clicks "Publish"
  └─ fetch POST /post/new (FormData) → PostController::newAction
       ├─ Form valid and title unique
       │    └─ Post saved to DB
       │       → { success: true, post: { id, title, created } }
       │         JS shows success toast
       │         JS broadcasts via WebSocket (see Exercise 04)
       │
       └─ Form invalid (e.g. duplicate title)
            → { success: false, message: "..." }
              JS shows error toast
```

The post list is rendered server-side by Twig on page load (sorted by date descending). New posts are added to the top of the list dynamically by JavaScript after a successful Ajax submission, without any page reload.

---

### Exercise 03 — View & Delete Posts (Ajax + Modal)

Add the ability to view post details in a modal and delete posts via Ajax.

**`PostController::viewAction`** — route `GET /view/{id}`

Returns all post details as JSON. The `canDelete` field is `true` if the user is logged in.

**`PostController::deleteAction`** — route `DELETE /delete/{id}`, requires `ROLE_USER`

Deletes the post and returns its `id` as JSON.

**Frontend changes in `index.html.twig`:**

- Each post title is clickable — clicking it calls `/view/{id}` via Ajax and opens a modal with the post details
- The modal contains a delete button (visible only when logged in) which shows a confirmation dialog before calling `/delete/{id}`
- On successful deletion, the post is removed from the list and the modal is closed
- The modal can be closed via the × button, a click outside, or the `Escape` key

**Flow:**

```
User clicks a post title
  └─ fetch GET /view/{id} → PostController::viewAction
       └─ Returns { success: true, post: { id, title, content, created, canDelete } }
          JS populates and opens the modal
          Delete button is shown only if canDelete === true

User clicks "Delete" in the modal
  └─ confirm() dialog shown
       ├─ User cancels → nothing happens
       └─ User confirms
            └─ fetch DELETE /delete/{id} → PostController::deleteAction
                 └─ Post removed from DB
                    → { success: true, id }
                    JS broadcasts via WebSocket (see Exercise 04)
                    Modal closes
```

---

### Exercise 04 — Real-Time Updates with WebSockets

Broadcast post creations and deletions to all connected clients in real time.

**Library used: Workerman**

Ratchet (the most common Symfony WebSocket library) is not compatible with Symfony 7.x, so Workerman is used instead as it has no Symfony dependency conflicts.

**1. Create the WebSocket command**

File: `src/Command/WebsocketServerCommand.php`

The command starts a Workerman WebSocket server on port `8080`. On receiving a message from any client, it broadcasts it to all connected clients.

```bash
php bin/console websocket:server
```

**2. Frontend WebSocket client in `index.html.twig`:**

- On page load, a `WebSocket` connection is opened to `ws://localhost:8080`
- When a post is **created**: instead of updating the DOM directly, the client sends `{ type: 'post_created', post: {...} }` to the WS server, which rebroadcasts it to everyone — all tabs update their list
- When a post is **deleted**: same pattern — the client sends `{ type: 'post_deleted', id: ... }` and all tabs remove the post from their list and close the modal if it was open

**Flow:**

```
Two browser tabs are open on /

Tab 1 — creates a post:
  └─ fetch POST /post/new → Symfony saves to DB → { success: true, post: {...} }
     ws.send({ type: 'post_created', post: {...} })
       └─ Workerman receives the message
          └─ Broadcasts to ALL connected clients (Tab 1 + Tab 2)
               ├─ Tab 1: onmessage → addPostToList() → post appears in list
               └─ Tab 2: onmessage → addPostToList() → post appears in list

Tab 1 — deletes a post:
  └─ fetch DELETE /delete/{id} → Symfony deletes from DB → { success: true, id }
     ws.send({ type: 'post_deleted', id })
       └─ Workerman broadcasts to ALL clients
               ├─ Tab 1: onmessage → removes <li> from DOM, closes modal
               └─ Tab 2: onmessage → removes <li> from DOM, closes modal if open
```

The key design decision: Symfony (HTTP) is responsible for persisting data to the database. Workerman (WebSocket) is only responsible for notifying all clients. The two servers are completely independent — they never talk to each other directly.

---

## Useful Commands

### Local

```bash
php bin/console app:create-user                                   # Create a new user
php bin/console cache:clear                                       # Clear the cache
php bin/console debug:router                                      # List all routes
php bin/console dbal:run-sql "SELECT email, username FROM user"   # Inspect users in DB
php bin/console dbal:run-sql "SELECT * FROM post"                 # Inspect posts in DB
php bin/console websocket:server                                  # Start the WebSocket server
```

### Docker

```bash
make build          # Build the Docker image
make up             # Start the containers (foreground)
make up-d           # Start the containers (background)
make down           # Stop the containers
make restart        # Rebuild and restart
make shell          # Open a bash shell inside the container
make create-user    # Create a new user
make cache-clear    # Clear the Symfony cache
make routes         # List all routes
make db-users       # Inspect users in DB
make db-posts       # Inspect posts in DB
make logs           # Follow container logs
```