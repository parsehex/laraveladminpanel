<div
    x-data="{ open: {{ ($errors->has('suggestion') || $errors->has('urgency') || $errors->has('page_url')) ? 'true' : 'false' }} }"
    x-cloak
    class="fixed bottom-6 right-6 z-50"
    @keydown.escape.window="open = false"
>
    <button
        type="button"
        @click="open = true"
        class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-600 text-white shadow-lg shadow-blue-600/30 transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        title="Send website feedback"
        aria-label="Send website feedback"
    >
        <i class="fas fa-comment-dots text-xl"></i>
    </button>

    <div
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-50 bg-slate-950/50 backdrop-blur-sm"
        @click="open = false"
    ></div>

    <div
        x-show="open"
        x-transition
        @click.stop
        class="fixed bottom-24 right-6 z-50 w-[min(100vw-3rem,24rem)] rounded-xl border border-gray-200 bg-white shadow-2xl"
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

            <div class="rounded-md bg-gray-50 px-3 py-2">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Page</p>
                <p class="mt-1 break-all text-xs text-gray-700">{{ url()->full() }}</p>
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
