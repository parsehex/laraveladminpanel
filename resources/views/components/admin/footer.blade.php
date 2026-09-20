@php
    $year = now()->year;
    $feedbackOpen = $errors->has('suggestion') || $errors->has('urgency') || $errors->has('page_url');
@endphp

<div
    x-data="{ open: {{ $feedbackOpen ? 'true' : 'false' }} }"
    @keydown.escape.window="open = false"
    class="shrink-0"
>
    <footer class="shrink-0 border-t border-slate-800 bg-slate-950 shadow-[0_-14px_34px_rgba(15,23,42,0.08)]">
        <div class="flex flex-col gap-2 px-5 py-3 text-xs text-slate-300 sm:flex-row sm:items-center sm:justify-between lg:px-8">
            <p>&copy; {{ $year }} Ben's Appliances. All rights reserved.</p>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                <button
                    type="button"
                    @click="open = true"
                    class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-3 py-1.5 font-semibold text-white transition hover:bg-blue-700"
                >
                    <i class="fas fa-comment-dots"></i>
                    Submit Feedback
                </button>
            </div>
        </div>
    </footer>

    <div
        x-cloak
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-[1100] bg-slate-950/50 backdrop-blur-sm"
        @click="open = false"
    ></div>

    <div
        x-cloak
        x-show="open"
        x-transition
        @click.stop
        class="fixed bottom-20 right-6 z-[1101] w-[min(100vw-3rem,24rem)] rounded-xl border border-gray-200 bg-white shadow-2xl"
        role="dialog"
        aria-modal="true"
        aria-labelledby="suggestion-fab-title"
    >
        <div class="flex items-start justify-between border-b border-gray-200 px-5 py-4">
            <div>
                <h2 id="suggestion-fab-title" class="text-base font-semibold text-gray-900">Website feedback</h2>
                <p class="mt-1 text-xs text-gray-500">Your current page will be included automatically.</p>
            </div>
            <button
                type="button"
                @click="open = false"
                class="rounded-md p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                aria-label="Close feedback form"
            >
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.suggestions.store') }}" class="space-y-4 p-5">
            @csrf
            <input type="hidden" name="page_url" value="{{ url()->full() }}">

            <div>
                <label for="suggestion-fab-message" class="mb-1 block text-sm font-medium text-gray-700">Suggestion</label>
                <textarea
                    id="suggestion-fab-message"
                    name="suggestion"
                    rows="4"
                    required
                    class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Share a workflow issue, improvement, or dashboard request..."
                >{{ old('suggestion') }}</textarea>
            </div>

            <div>
                <label for="suggestion-fab-urgency" class="mb-1 block text-sm font-medium text-gray-700">Urgency</label>
                <select
                    id="suggestion-fab-urgency"
                    name="urgency"
                    class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                    <option value="normal" @selected(old('urgency', 'normal') === 'normal')>Normal</option>
                    <option value="high" @selected(old('urgency') === 'high')>High</option>
                    <option value="low" @selected(old('urgency') === 'low')>Low</option>
                </select>
            </div>

            <div class="flex justify-end gap-2 pt-1">
                <button
                    type="button"
                    @click="open = false"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                >
                    Submit
                </button>
            </div>
        </form>
    </div>
</div>
