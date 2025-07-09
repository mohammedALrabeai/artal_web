<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>عارض PDF - {{ $pdfDocument->title }}</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- PDF.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- ملف CSS الإضافي -->
    <link href="{{ asset('css/pdf-viewer.css' ) }}" rel="stylesheet">
    
    <style>
        /* ... كود الـ CSS الخاص بك هنا ... */
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        .container-fluid {
            padding: 20px;
        }
        .pdf-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: calc(100vh - 40px);
        }
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: #e9ecef;
            border-bottom: 1px solid #dee2e6;
            flex-wrap: wrap;
            gap: 10px;
        }
        .toolbar h5 {
            color: #343a40;
        }
        .page-controls, .zoom-controls, .action-buttons {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .pdf-viewer {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: auto;
            position: relative;
            padding: 20px;
            background-color: #f0f2f5;
        }
        .pdf-canvas {
            border: 1px solid #ccc;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            display: block;
            margin: 0 auto;
        }
        .loading-spinner {
            text-align: center;
            color: #6c757d;
        }
        .text-field-overlay {
            position: absolute;
            border: 1px dashed #007bff;
            background-color: rgba(0, 123, 255, 0.1);
            box-sizing: border-box;
            display: none; /* مخفي افتراضيا */
            overflow: hidden;
        }
        .text-field-overlay.edit-mode {
            border: 2px solid #28a745; /* حدود خضراء في وضع التحرير */
            background-color: rgba(40, 167, 69, 0.15); /* خلفية خضراء فاتحة */
        }
        .text-field-overlay .field-label {
            position: absolute;
            top: -20px;
            left: 0;
            background-color: #007bff;
            color: white;
            padding: 2px 5px;
            font-size: 10px;
            border-radius: 3px;
            white-space: nowrap;
            display: none; /* مخفي افتراضيا */
        }
        .text-field-overlay.edit-mode .field-label {
            display: block; /* يظهر في وضع التحرير */
        }
        .text-field-overlay .field-input {
            width: 100%;
            height: 100%;
            border: none;
            background: transparent;
            padding: 2px;
            box-sizing: border-box;
            font-size: inherit;
            font-family: inherit;
            color: inherit;
            resize: none; /* لمنع تغيير حجم textarea يدويا */
        }
        .text-field-overlay.edit-mode .field-input {
            border: 1px solid #007bff; /* حدود للإدخال في وضع التحرير */
        }
        .success-message {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            display: none;
            padding: 10px 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .no-print {
            /* لعدم طباعة شريط الأدوات */
            @media print {
                display: none !important;
            }
        }
        /* تحسينات الطباعة */
        @media print {
            body {
                margin: 0;
                padding: 0;
                overflow: hidden;
            }
            .pdf-viewer {
                padding: 0;
                margin: 0;
                box-shadow: none;
                overflow: hidden;
                display: block;
                height: auto;
            }
            .pdf-canvas {
                width: 100% !important;
                height: auto !important;
                display: block;
                margin: 0;
                border: none;
                box-shadow: none;
            }
            .text-field-overlay {
                border: none !important;
                background-color: transparent !important;
                display: block !important; /* تأكد من ظهورها عند الطباعة */
            }
            .text-field-overlay .field-label {
                display: none !important; /* إخفاء التسمية عند الطباعة */
            }
            .text-field-overlay .field-input {
                border: none !important;
                background: transparent !important;
                -webkit-print-color-adjust: exact; /* لطباعة الألوان والخلفيات */
                color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- رسالة النجاح -->
        <div class="alert alert-success success-message" id="successMessage">
            <i class="fas fa-check-circle"></i> تم حفظ البيانات بنجاح
        </div>
        
        <!-- شريط الأدوات -->
        <div class="pdf-container">
            <div class="toolbar no-print">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 me-3">
                        <i class="fas fa-file-pdf text-danger"></i>
                        {{ $pdfDocument->title }}
                    </h5>
                </div>
                
                <div class="page-controls">
                    <button class="btn btn-outline-secondary" id="prevPage">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <span class="mx-2">
                        صفحة <span id="pageNum">1</span> من <span id="pageCount">-</span>
                    </span>
                    <button class="btn btn-outline-secondary" id="nextPage">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                
                <div class="zoom-controls">
                    <button class="btn btn-outline-secondary" id="zoomOut">
                        <i class="fas fa-search-minus"></i>
                    </button>
                    <span class="mx-2" id="zoomLevel">100%</span>
                    <button class="btn btn-outline-secondary" id="zoomIn">
                        <i class="fas fa-search-plus"></i>
                    </button>
                </div>
                
                <div class="action-buttons">
                    <button class="btn btn-success" id="saveData">
                        <i class="fas fa-save"></i> حفظ البيانات
                    </button>
                    <button class="btn btn-primary" id="printPdf">
                        <i class="fas fa-print"></i> طباعة
                    </button>
                </div>
            </div>
            
            <!-- منطقة عرض PDF -->
            <div class="pdf-viewer" id="pdfViewer">
                <div class="loading-spinner">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                    <p class="mt-2">جاري تحميل المستند...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // إعدادات PDF.js
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        
        // **جديد: تمرير ID المستند والـ URLs إلى JavaScript**
        const currentPdfDocumentId = {{ $pdfDocument->id }};
        const saveFieldDataUrl = "{{ route('pdf.save-field-data', $pdfDocument ) }}";
        const generatePrintablePdfUrl = "{{ route('pdf.generate-printable', $pdfDocument) }}";

        // **تعريف كلاس PdfViewer أولاً**
        class PdfViewer {
            constructor() {
                this.pdfDoc = null;
                this.pageNum = 1;
                this.pageRendering = false;
                this.pageNumPending = null;
                this.scale = 1.2;
                this.canvas = null;
                this.ctx = null;
                this.textFields = @json($pdfDocument->textFields);
                this.fieldData = {};
                
                this.init();
            }
            
            async init() {
                try {
                    // تحميل PDF
                    const loadingTask = pdfjsLib.getDocument('{{ $pdfDocument->file_url }}');
                    this.pdfDoc = await loadingTask.promise;
                    
                    document.getElementById('pageCount').textContent = this.pdfDoc.numPages;
                    
                    // إنشاء canvas
                    this.createCanvas();
                    
                    // رسم الصفحة الأولى
                    await this.renderPage(this.pageNum);
                    
                    // إضافة حقول النص
                    this.addTextFields();
                    
                    // ربط الأحداث
                    this.bindEvents();
                    
                } catch (error) {
                    console.error('خطأ في تحميل PDF:', error);
                    document.getElementById('pdfViewer').innerHTML = 
                        '<div class="alert alert-danger">خطأ في تحميل المستند</div>';
                }
            }
            
            createCanvas() {
                const viewer = document.getElementById('pdfViewer');
                viewer.innerHTML = '';
                
                const canvasContainer = document.createElement('div');
                canvasContainer.style.position = 'relative';
                canvasContainer.style.display = 'inline-block';
                
                this.canvas = document.createElement('canvas');
                this.canvas.className = 'pdf-canvas';
                this.ctx = this.canvas.getContext('2d');
                
                canvasContainer.appendChild(this.canvas);
                viewer.appendChild(canvasContainer);
            }
            
            async renderPage(num) {
                this.pageRendering = true;
                
                try {
                    const page = await this.pdfDoc.getPage(num);
                    const viewport = page.getViewport({ scale: this.scale });
                    
                    this.canvas.height = viewport.height;
                    this.canvas.width = viewport.width;
                    
                    const renderContext = {
                        canvasContext: this.ctx,
                        viewport: viewport
                    };
                    
                    await page.render(renderContext).promise;
                    
                    this.pageRendering = false;
                    
                    if (this.pageNumPending !== null) {
                        this.renderPage(this.pageNumPending);
                        this.pageNumPending = null;
                    }
                    
                    // تحديث حقول النص للصفحة الحالية
                    this.updateTextFieldsForCurrentPage();
                    
                } catch (error) {
                    console.error('خطأ في رسم الصفحة:', error);
                    this.pageRendering = false;
                }
            }
            
            queueRenderPage(num) {
                if (this.pageRendering) {
                    this.pageNumPending = num;
                } else {
                    this.renderPage(num);
                }
            }
            
            addTextFields() {
                const canvasContainer = this.canvas.parentElement;
                
                this.textFields.forEach(field => {
                    const fieldElement = document.createElement('div');
                    fieldElement.className = 'text-field-overlay';
                    fieldElement.dataset.fieldId = field.id;
                    fieldElement.dataset.pageNumber = field.page_number;
                    
                    // إضافة تسمية الحقل
                    const label = document.createElement('div');
                    label.className = 'field-label';
                    label.textContent = field.field_label;
                    fieldElement.appendChild(label);
                    
                    // إضافة حقل الإدخال
                    const input = document.createElement(field.field_type === 'textarea' ? 'textarea' : 'input');
                    input.className = 'field-input';
                    input.type = field.field_type === 'textarea' ? undefined : field.field_type;
                    input.placeholder = field.placeholder || '';
                    input.required = field.is_required;
                    input.dataset.fieldName = field.field_name;
                    
                    // حفظ البيانات عند التغيير
                    input.addEventListener('input', (e) => {
                        this.fieldData[field.field_name] = e.target.value;
                    });
                    
                    fieldElement.appendChild(input);
                    canvasContainer.appendChild(fieldElement);
                });
                
                this.updateTextFieldsForCurrentPage();
            }
            
            updateTextFieldsForCurrentPage() {
                const canvasRect = this.canvas.getBoundingClientRect();
                const canvasContainer = this.canvas.parentElement;
                
                this.textFields.forEach(field => {
                    const fieldElement = canvasContainer.querySelector(`[data-field-id="${field.id}"]`);
                    if (!fieldElement) return;
                    
                    if (field.page_number === this.pageNum) {
                        // حساب الموقع النسبي
                        const x = (field.x_position / 100) * this.canvas.width;
                        const y = (field.y_position / 100) * this.canvas.height;
                        const width = (field.width / 100) * this.canvas.width;
                        const height = (field.height / 100) * this.canvas.height;
                        
                        fieldElement.style.display = 'block';
                        fieldElement.style.left = x + 'px';
                        fieldElement.style.top = y + 'px';
                        fieldElement.style.width = width + 'px';
                        fieldElement.style.height = height + 'px';
                        fieldElement.style.fontSize = field.font_size + 'px';
                        fieldElement.style.fontFamily = field.font_family;
                        fieldElement.style.color = field.text_color;
                    } else {
                        fieldElement.style.display = 'none';
                    }
                });
            }
            
            bindEvents() {
                // التنقل بين الصفحات
                document.getElementById('prevPage').addEventListener('click', () => {
                    if (this.pageNum <= 1) return;
                    this.pageNum--;
                    document.getElementById('pageNum').textContent = this.pageNum;
                    this.queueRenderPage(this.pageNum);
                });
                
                document.getElementById('nextPage').addEventListener('click', () => {
                    if (this.pageNum >= this.pdfDoc.numPages) return;
                    this.pageNum++;
                    document.getElementById('pageNum').textContent = this.pageNum;
                    this.queueRenderPage(this.pageNum);
                });
                
                // التكبير والتصغير
                document.getElementById('zoomIn').addEventListener('click', () => {
                    this.scale += 0.2;
                    this.updateZoomLevel();
                    this.queueRenderPage(this.pageNum);
                });
                
                document.getElementById('zoomOut').addEventListener('click', () => {
                    if (this.scale <= 0.4) return;
                    this.scale -= 0.2;
                    this.updateZoomLevel();
                    this.queueRenderPage(this.pageNum);
                });
                
                // حفظ البيانات
                document.getElementById('saveData').addEventListener('click', () => {
                    this.saveFieldData();
                });
                
                // الطباعة
                document.getElementById('printPdf').addEventListener('click', () => {
                    this.printDocument();
                });
            }
            
            updateZoomLevel() {
                document.getElementById('zoomLevel').textContent = Math.round(this.scale * 100) + '%';
            }
            
            async saveFieldData() {
                try {
                    const response = await fetch(saveFieldDataUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            field_data: this.fieldData,
                            pdf_document_id: currentPdfDocumentId // إضافة ID المستند هنا
                        })
                    });
                    
                    if (response.ok) {
                        this.showSuccessMessage();
                    } else {
                        throw new Error('فشل في حفظ البيانات');
                    }
                } catch (error) {
                    console.error('خطأ في حفظ البيانات:', error);
                    alert('حدث خطأ في حفظ البيانات');
                }
            }
            
            showSuccessMessage() {
                const message = document.getElementById('successMessage');
                message.style.display = 'block';
                setTimeout(() => {
                    message.style.display = 'none';
                }, 3000);
            }
            
            printDocument() {
                // حفظ البيانات قبل الطباعة
                this.saveFieldData();
                
                // طباعة الصفحة
                setTimeout(() => {
                    window.print();
                }, 500);
            }
        }
    </script>
    
    <!-- **تحميل ملف pdf-viewer.js بعد تعريف PdfViewer** -->
    <script src="{{ asset('js/pdf-viewer.js') }}"></script>

    <script>
        // تشغيل التطبيق عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', () => {
            new AdvancedPdfViewer();
        });
    </script>
</body>
</html>
