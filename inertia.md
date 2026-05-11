# How Laravel + Inertia + Vue Communicate

## Simple Explanation

Think of Inertia as the bridge between Laravel and Vue.

```text
Laravel = backend/brain
Vue = frontend/UI
Inertia = translator between them
```

Laravel handles:
- routes
- database
- validation
- authentication

Vue handles:
- UI
- components
- reactivity
- frontend experience

Inertia connects them together without needing a separate API.

---

# Traditional Laravel (Blade)

Flow:

```text
Browser → Laravel Route → Controller → Blade View → HTML
```

Example:

```php
return view('dashboard', [
    'users' => $users
]);
```

Laravel renders the HTML directly.

---

# SPA/API Architecture

In a normal Vue SPA:

```text
Vue → API Request → Laravel API → JSON → Vue Updates UI
```

Example:

```js
axios.get('/api/users')
```

Laravel only returns JSON.

Vue controls everything else.

---

# Laravel + Inertia Flow

With Inertia:

```text
Browser → Laravel Route → Controller → Inertia → Vue Page
```

This combines:
- Laravel backend simplicity
- Vue SPA experience

---

# Step-by-Step Example

---

## Step 1 — User Visits a Route

Laravel route:

```php
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard', [
        'users' => User::all()
    ]);
});
```

---

## Step 2 — Laravel Responds

### First visit (full page load)

The FIRST request loads the full HTML page. Laravel renders the Vue component to HTML using the `@inertia` Blade directive in the root layout. The props are embedded as JSON inside a `<script>` tag so Vue can hydrate on the client. No AJAX involved.

### Subsequent navigations (SPA mode)

For every visit after that, Inertia's client sends a header `X-Inertia: true`. Laravel detects this and skips the HTML render — it only returns JSON:

```json
{
  "component": "Dashboard",
  "props": {
    "users": [...]
  }
}
```

No full HTML, no page reload.

---

## Step 3 — Vue Receives the Data

Vue sees:

```text
component = Dashboard
```

So it loads:

```text
resources/js/Pages/Dashboard.vue
```

And receives the props:

```js
users
```

---

## Step 4 — Vue Renders the Page

Example Vue page:

```vue
<script setup>
defineProps({
  users: Array
})
</script>

<template>
  <div v-for="user in users">
    {{ user.name }}
  </div>
</template>
```

Vue renders the users dynamically.

---

# Navigation Flow

Example link:

```vue
<Link href="/users">Users</Link>
```

What happens:

1. Inertia intercepts the click
2. Sends AJAX request to Laravel
3. Laravel returns:
   - component name
   - props/data
4. Vue swaps the page dynamically
5. No full browser refresh

---

# Form Mutations (The Critical Rule)

When you submit a form (POST, PUT, DELETE) via Inertia:

```vue
<button @click="router.post('/users', formData)">Save</button>
```

The controller MAY NOT return JSON or a view. It MUST return a **redirect**:

```php
public function store(Request $request)
{
    User::create($request->all());
    return back();           // ✅ redirect
    // return inertia(...)   // ❌ will break
}
```

Inertia's client receives the 302 redirect response, then **automatically follows it** with a GET request to fetch the updated page with fresh props.

## Why This Matters

Because the page is re-fetched after every mutation, **the server is always the single source of truth**. You should NOT copy props into local `ref()` — those copies go stale. Read from `defineProps` directly.

```vue
<!-- ✅ Correct: use props directly -->
<div v-for="addr in addresses">{{ addr.name }}</div>

<!-- ❌ Wrong: copying breaks reactivity after mutations -->
<script setup>
const localAddresses = ref([...props.addresses])
</script>
```

---

# Who Controls What?

| Part | Controlled By |
|------|---------------|
| Routes | Laravel |
| Database | Laravel |
| Authentication | Laravel |
| Validation | Laravel |
| UI Rendering | Vue |
| Navigation | Inertia |
| Props/Data | Laravel → Vue |

---

# Why Inertia Is Popular

## Benefits

### Laravel Simplicity
- routes
- middleware
- validation
- sessions
- authentication

### Vue SPA Experience
- smooth navigation
- reactive UI
- reusable components

WITHOUT:
- building a separate API
- handling token auth everywhere
- maintaining two separate apps

---

# Mental Model

```text
Laravel decides WHAT page/data to show.
Inertia transports it.
Vue displays it.
```

---

# Main Difference From Traditional APIs

## API Architecture

```text
Frontend App ↔ Backend API
```

Two separate systems.

---

## Inertia Architecture

```text
Laravel + Vue behave like one application
```

Much simpler for many full-stack projects.

---