<?php
$page_title = 'Registration Successful - Rhapathon';
include_once 'includes/header.php';

$name = trim((string) ($_GET['name'] ?? ''));
$registrationId = trim((string) ($_GET['registration_id'] ?? ''));
?>

<main class="min-h-screen pt-28 pb-16 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-white via-light to-white">
    <section class="max-w-3xl mx-auto">
        <div class="bg-white border border-border rounded-[32px] shadow-sm overflow-hidden">
            <div class="px-6 sm:px-10 pt-10 sm:pt-14 pb-8 text-center">
                <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-green-50 border border-green-200">
                    <svg class="h-10 w-10 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 6 9 17l-5-5"></path>
                    </svg>
                </div>
                <p class="text-sm font-semibold tracking-[0.22em] uppercase text-accent">Registration Complete</p>
                <h1 class="mt-3 text-3xl sm:text-5xl font-black text-primary">You are successfully registered.</h1>
                <p class="mt-5 text-base sm:text-lg text-slate-600 leading-8">
                    <?php if ($name !== ''): ?>
                        Thank you, <span class="font-semibold text-primary"><?php echo htmlspecialchars($name); ?></span>. Your Rhapathon registration has been received.
                    <?php else: ?>
                        Your Rhapathon registration has been received successfully.
                    <?php endif; ?>
                </p>
            </div>

            <div class="px-6 sm:px-10 pb-10 sm:pb-12">
                <div class="rounded-3xl border border-slate-200 bg-slate-50 px-5 sm:px-6 py-5 sm:py-6">
                    <h2 class="text-lg font-bold text-primary">What happens next</h2>
                    <p class="mt-3 text-sm sm:text-base text-slate-700 leading-7">
                        Please keep your registration details safe. If you need to register another participant, you can return to the registration form using the button below.
                    </p>
                    <?php if ($registrationId !== ''): ?>
                        <p class="mt-4 text-sm text-slate-500">
                            Registration ID:
                            <span class="font-semibold text-slate-700"><?php echo htmlspecialchars($registrationId); ?></span>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="register" class="inline-flex items-center justify-center rounded-full bg-primary px-8 py-4 text-base font-semibold text-white hover:bg-[#00006b] transition-colors">
                        Back to Registration
                    </a>
                    <a href="/" class="inline-flex items-center justify-center rounded-full border border-primary px-8 py-4 text-base font-semibold text-primary hover:bg-slate-50 transition-colors">
                        Home
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include_once 'includes/footer.php'; ?>
