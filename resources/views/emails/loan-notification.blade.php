<p>Dear {{ $loan->bank->name }},</p>

<p>
    This is to notify you that an employee with the following details has submitted a resignation:
</p>

<p>
    <strong>Employee ID:</strong> {{ $loan->employee_id }}<br>
    <strong>Loan Amount:</strong> {{ $loan->amount }}<br>
    <strong>Start Date:</strong> {{ $loan->start_date }}<br>
    <strong>End Date:</strong> {{ $loan->end_date }}
</p>

<p>Thank you.</p>
