<!DOCTYPE html>
<html lang="en-US">
<head>
    @include('partials.head')
    <title>Sign in · Environet back office</title>
    <meta name="robots" content="noindex">
</head>
<body class="admin lanes grid min-h-screen place-items-center bg-navy-900 px-4 py-10 text-white">
    <main class="w-full max-w-sm">
        <a href="{{ route('home') }}" class="mx-auto mb-8 flex min-h-11 w-max items-center"><x-logo tone="light" /></a>
        <form method="POST" action="{{ route('login') }}" class="a-card space-y-5 p-6 text-ink sm:p-8">
            @csrf
            <div>
                <h1 class="text-xl font-semibold">Back office</h1>
                <p class="mt-1 text-sm text-ink-3">RFQs and bids, teaming and vendors, careers and document rooms. Staff only.</p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold" for="email">Email</label>
                <input class="a-input w-full" id="email" name="email" type="email" value="{{ input_string(old('email')) }}" required autofocus autocomplete="username" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                <x-error for="email" />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold" for="password">Password</label>
                <input class="a-input w-full" id="password" name="password" type="password" required autocomplete="current-password">
            </div>
            <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1" class="h-5 w-5 accent-[var(--primary)]">Keep me signed in on this device</label>
            <button class="a-btn a-btn-primary w-full">Sign in</button>
        </form>
    </main>
</body>
</html>
