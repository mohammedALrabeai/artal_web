<?php
require_once('tcpdf_include.php');

class PDF extends TCPDF {
    public function Header() {
        // Set the image as a background
        $img_file = public_path('images/back.jpg'); // المسار المناسب داخل Laravel
        $this->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
   }

    public function Footer() {
        // Footer content (if needed)
    }
}

$pdf = new PDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your Company');
$pdf->SetTitle('Employee Hiring and Onboarding Form');

// Set margins
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(FALSE, 0);
// إعداد الاتجاه إلى اليمين إلى الشمال
// $pdf->setLanguageArray([
//     'a_meta_charset' => 'UTF-8',
//     'a_meta_dir' => 'rtl',
//     'a_meta_language' => 'ar',
//     'w_page' => 'page',
// ]);


// Set font
$pdf->SetFont('aealarabiya', '', 10);

// Add a page
$pdf->AddPage();

// Sample variables
$employee_name = 'محمد عبد الـله الربيعي';
$employee_id = '12345';
$national_id = '987654321';
$birth_date = '1990-01-01';
$phone_number = '1234567890';
$contact_numbers = '123-456-7890';
$email = 'john.doe@example.com';
$bank_name = 'ABC Bank';
$iban = 'SA1234567890123456789012';
$address = '123 Main St, City, Country';
$job_title = 'Security Guard';
$contract_type = 'Fixed-term';
$department = 'Operations';
$work_location = 'Project Alpha';
$start_date = '2024-01-01';
$onboarding_type = 'New onboarding';
$supervisor_name = 'Jane Smith';
$approver_name = 'James Brown';

// Set positions and add content
$pdf->SetFont('dejavusans', '', 12);

// Personal Information Section
$pdf->SetXY(40, 29.5); // Position for Employee Name
$pdf->Cell(0, 0, $employee_id, 0, 1);

$pdf->SetXY(85, 29.5); 
$pdf->Cell(0, 0, $national_id, 0, 1);

$pdf->SetXY(140, 29.5); // Position for Employee ID
$pdf->Cell(0, 0, $employee_name, 0, 1); 

$pdf->SetFont('aealarabiya', '', 12);


$pdf->SetXY(140, 35); // Position for Birth Date
$pdf->Cell(0, 0, $birth_date, 0, 1);

$pdf->SetXY(84,35); 
$pdf->Cell(0, 0, $contact_numbers, 0, 1);
$pdf->SetFont('aealarabiya', '', 12);

$pdf->SetXY(10, 35); // Position for Employee Name
$pdf->Cell(0, 0, $email, 0, 1);





$pdf->SetXY(10, 41); // Position for Birth Date
$pdf->Cell(0, 0, $address, 0, 1,'C');



$pdf->SetXY(120, 46); // Position for Birth Date
$pdf->Cell(0, 0, $bank_name, 0, 1,'C');



$pdf->SetXY(10, 46); // Position for Employee Name
$pdf->Cell(0, 0, $iban, 0, 1);

$pdf->SetXY(100, 80); // Position for Email
$pdf->Cell(0, 0, $work_location, 0, 1);



// $pdf->SetXY(40, 70); // Position for Contact Numbers
// $pdf->Cell(0, 0, $contact_numbers, 0, 1);

$pdf->SetXY(140, 120); // Position for Contract Type
$pdf->Cell(0, 0, $contract_type, 0, 1);





$pdf->SetXY(140, 120); // Position for Contract Type
$pdf->Cell(0, 0, $contract_type, 0, 1);




$pdf->SetXY(140, 163); // Position for Onboarding Type
$pdf->Cell(0, 0, $employee_name, 0, 1);

// Approvals Section
$pdf->SetXY(33, 204); // Position for Supervisor Name
$pdf->Cell(0, 0, $supervisor_name, 0, 1);

$pdf->SetXY(88, 204); // Position for Supervisor Name
$pdf->Cell(0, 0, $supervisor_name, 0, 1);

$pdf->SetXY(154, 204); // Position for Approver Name
$pdf->Cell(0, 0, $approver_name, 0, 1);

// Output the PDF
$pdf->Output('employee_hiring_form.pdf', 'I');
