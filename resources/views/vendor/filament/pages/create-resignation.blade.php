<div>
    <form wire:submit.prevent="confirmResignation">
        @if (!empty($loanDetails))
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-75">
                <div class="p-6 bg-white rounded shadow-lg w-96">
                    <h2 class="mb-4 text-lg font-bold">Active Loans Found</h2>
                    <p class="mb-4">The employee has the following active loans:</p>
                    <ul class="pl-5 mb-4 list-disc">
                        @foreach ($loanDetails as $loan)
                            <li>
                                <strong>Bank:</strong> {{ $loan['bank'] }},
                                <strong>Amount:</strong> {{ $loan['amount'] }},
                                <strong>Start Date:</strong> {{ $loan['start_date'] }},
                                <strong>End Date:</strong> {{ $loan['end_date'] }}
                            </li>
                        @endforeach
                    </ul>
                    <p>By proceeding, an email will be sent to the respective banks.</p>
                    <div class="flex justify-end mt-4">
                        <button type="button" wire:click="$set('loanDetails', [])" class="px-4 py-2 mr-2 text-gray-700 bg-gray-300 rounded">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-white bg-blue-500 rounded">
                            Proceed
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </form>
</div>
