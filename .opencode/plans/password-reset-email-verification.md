# Password Reset + Email Verification Implementation

## Files to Create

### 1. `app/Notifications/SendPasswordResetLink.php`

```php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendPasswordResetLink extends Notification
{
    use Queueable;

    public function __construct(
        public string $token
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset.form', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);

        return (new MailMessage)
            ->subject('Reset Your Password')
            ->greeting('Hello!')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', $url)
            ->line('This password reset link will expire in 60 minutes.')
            ->line('If you did not request a password reset, no further action is required.');
    }
}
```

### 2. `app/Http/Controllers/PasswordResetController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Notifications\SendPasswordResetLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PasswordResetController extends Controller
{
    public function forgotPassword()
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::broker()->sendResetLink(
            $request->only('email'),
            function ($user, $token) {
                $user->notify(new SendPasswordResetLink($token));
            }
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', 'We have emailed your password reset link!');
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }

    public function resetPasswordForm(string $token, Request $request)
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => bcrypt($password),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', 'Your password has been reset! Please sign in.');
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
```

### 3. `app/Http/Controllers/VerificationController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

class VerificationController extends Controller
{
    public function showNotice(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        return Inertia::render('Auth/VerifyEmail');
    }

    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (!URL::hasValidSignature($request)) {
            return redirect()->route('login')->with('error', 'Invalid or expired verification link.');
        }

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return redirect()->route('login')->with('error', 'Invalid verification link.');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login')->with('success', 'Email already verified.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->route('login')->with('success', 'Email verified successfully! You can now sign in.');
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Verification link sent!');
    }
}
```

### 4. `resources/js/Pages/Auth/ForgotPassword.vue`

```vue
<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="text-center text-3xl font-bold">Forgot your password?</h2>
                <p class="mt-2 text-center text-gray-600">Enter your email and we'll send you a reset link.</p>
            </div>
            <div v-if="$page.props.flash?.success"
                class="bg-green-50 text-green-700 p-4 rounded-lg text-sm text-center">
                {{ $page.props.flash.success }}
            </div>
            <form v-else class="mt-8 space-y-6" @submit.prevent="submit">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input v-model="form.email" type="email" required
                        class="mt-1 block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500"
                        :class="{ 'border-red-500': errors.email }">
                    <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
                </div>
                <button type="submit" :disabled="processing"
                    class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 disabled:opacity-50">
                    <span v-if="processing">Sending...</span>
                    <span v-else>Send Reset Link</span>
                </button>
            </form>
            <p class="text-center">
                <Link :href="route('login')" class="text-indigo-600 hover:text-indigo-700">Back to sign in</Link>
            </p>
        </div>
    </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps({
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const processing = ref(false);
const form = reactive({
    email: '',
});

function submit() {
    processing.value = true;
    router.post(route('password.email'), form, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
        },
    });
}
</script>
```

### 5. `resources/js/Pages/Auth/ResetPassword.vue`

```vue
<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="text-center text-3xl font-bold">Reset your password</h2>
            </div>
            <form class="mt-8 space-y-6" @submit.prevent="submit">
                <input type="hidden" v-model="form.token">
                <input type="hidden" v-model="form.email">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input :value="form.email" type="email" disabled
                        class="mt-1 block w-full px-3 py-2 border rounded-lg bg-gray-100 text-gray-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">New Password</label>
                    <input v-model="form.password" type="password" required
                        class="mt-1 block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                    <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                    <input v-model="form.password_confirmation" type="password" required
                        class="mt-1 block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>
                <button type="submit" :disabled="processing"
                    class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 disabled:opacity-50">
                    <span v-if="processing">Resetting...</span>
                    <span v-else>Reset Password</span>
                </button>
            </form>
            <p class="text-center">
                <Link :href="route('login')" class="text-indigo-600 hover:text-indigo-700">Back to sign in</Link>
            </p>
        </div>
    </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps({
    errors: {
        type: Object,
        default: () => ({}),
    },
    token: {
        type: String,
        required: true,
    },
    email: {
        type: String,
        required: true,
    },
});

const processing = ref(false);
const form = reactive({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit() {
    processing.value = true;
    router.post(route('password.update'), form, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
        },
    });
}
</script>
```

### 6. `resources/js/Pages/Auth/VerifyEmail.vue`

```vue
<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
        <div class="max-w-md w-full space-y-8 text-center">
            <div>
                <div class="mx-auto w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold">Verify your email</h2>
                <p class="mt-4 text-gray-600">
                    We sent a verification link to <strong>{{ user?.email }}</strong>.
                </p>
                <p class="mt-2 text-gray-500 text-sm">Click the link in the email to activate your account.</p>
            </div>
            <div
                v-if="$page.props.flash?.success"
                class="bg-green-50 text-green-700 p-4 rounded-lg text-sm">
                {{ $page.props.flash.success }}
            </div>
            <div class="mt-8 space-y-4">
                <button @click="resend" :disabled="sending"
                    class="w-full bg-indigo-600 text-white py-3 rounded-lg font-semibold hover:bg-indigo-700 disabled:opacity-50">
                    <span v-if="sending">Sending...</span>
                    <span v-else>Resend Verification Email</span>
                </button>
                <button @click="logout"
                    class="w-full text-gray-600 py-2 text-sm hover:text-gray-800">
                    Sign out
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const page = usePage();
const sending = ref(false);

const user = computed(() => page.props.auth?.user);

function resend() {
    sending.value = true;
    router.post(route('verification.resend'), {}, {
        preserveScroll: true,
        onFinish: () => {
            sending.value = false;
        },
    });
}

function logout() {
    router.post(route('logout'));
}
</script>
```

## Files to Modify

### 7. `app/Models/User.php`

Changes:
- Uncomment `use Illuminate\Contracts\Auth\MustVerifyEmail;`
- Implement `MustVerifyEmail` interface on the class

```diff
- // use Illuminate\Contracts\Auth\MustVerifyEmail;
+ use Illuminate\Contracts\Auth\MustVerifyEmail;
```

```diff
- class User extends Authenticatable
+ class User extends Authenticatable implements MustVerifyEmail
```

### 8. `app/Http/Controllers/AuthController.php`

In the `register()` method, add after `Auth::login($user);`:

```diff
+ $user->sendEmailVerificationNotification();
+ 
+ return Inertia::location(route('verification.notice'));
- return Inertia::location(route('home'));
```

### 9. `routes/web.php`

Add inside the `web` middleware group, after the auth routes section:

```php
// Password Reset
Route::get('/forgot-password', [PasswordResetController::class, 'forgotPassword'])->middleware('guest')->name('password.request');
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->middleware('guest')->name('password.email');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetPasswordForm'])->middleware('guest')->name('password.reset.form');
Route::post('/reset-password', [PasswordResetController::class, 'updatePassword'])->middleware('guest')->name('password.update');

// Email Verification
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [VerificationController::class, 'showNotice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
    Route::post('/email/resend', [VerificationController::class, 'resend'])->name('verification.resend');
});
```

Also add imports at the top:
```php
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\VerificationController;
```

### 10. `app/Http/Middleware/HandleInertiaRequests.php`

In the `share()` method, add flash messages to the returned array:

```php
'flash' => [
    'success' => fn () => $request->session()->get('success'),
    'error' => fn () => $request->session()->get('error'),
],
```

### 11. `resources/js/Pages/Auth/Login.vue`

Add a "Forgot your password?" link below the password field, before the submit button:

```diff
+ <div class="text-right">
+     <Link :href="route('password.request')" class="text-sm text-indigo-600 hover:text-indigo-700">
+         Forgot your password?
+     </Link>
+ </div>
```

Add `Link` import if not already there (it is).

Also add a flash success watcher to show a toast when redirected from password reset or verification:

```diff
+ import { watch, onMounted } from 'vue';
```

And add:
```js
+ onMounted(() => {
+     if (page.props.flash?.success) {
+         success(page.props.flash.success);
+     }
+ });
```

## Verification Steps

1. Run `php artisan route:list` to confirm all new routes are registered
2. Run `php artisan test` to check existing tests still pass
3. Register a new user → should redirect to `/email/verify` notice page
4. Check `storage/logs/laravel.log` for the verification email link
5. Open the verification link → should redirect to login with success message
6. Go to `/forgot-password` → enter email → check logs for reset link
7. Open reset link → enter new password → should redirect to login with success
8. Sign in with new password → should work
