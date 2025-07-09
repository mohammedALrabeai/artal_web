// ملف JavaScript إضافي للوظائف المتقدمة - يوضع في public/js/pdf-viewer.js

class AdvancedPdfViewer extends PdfViewer {
    constructor() {
        super();
        this.isDragging = false;
        this.dragElement = null;
        this.dragOffset = { x: 0, y: 0 };
        this.isEditMode = false;
        this.history = [];
        this.historyIndex = -1;
        
        this.initAdvancedFeatures();
    }
    
    initAdvancedFeatures() {
        this.addEditModeToggle();
        this.addKeyboardShortcuts();
        this.addContextMenu();
        this.addAutoSave();
        this.addFieldValidation();
    }
    
    addEditModeToggle() {
        const toolbar = document.querySelector(".toolbar .action-buttons");
        const editButton = document.createElement("button");
        editButton.className = "btn btn-warning";
        editButton.id = "toggleEditMode";
        editButton.innerHTML = "<i class=\"fas fa-edit\"></i> وضع التحرير";
        
        editButton.addEventListener("click", () => {
            this.toggleEditMode();
        });
        
        toolbar.insertBefore(editButton, toolbar.firstChild);
    }
    
    toggleEditMode() {
        this.isEditMode = !this.isEditMode;
        const button = document.getElementById("toggleEditMode");
        const fields = document.querySelectorAll(".text-field-overlay");
        
        if (this.isEditMode) {
            button.innerHTML = "<i class=\"fas fa-save\"></i> حفظ التخطيط";
            button.className = "btn btn-success";
            
            fields.forEach(field => {
                this.makeFieldDraggable(field);
                this.makeFieldResizable(field);
                field.classList.add("edit-mode");
            });
            
            this.showEditInstructions();
        } else {
            button.innerHTML = "<i class=\"fas fa-edit\"></i> وضع التحرير";
            button.className = "btn btn-warning";
            
            fields.forEach(field => {
                this.removeFieldDraggable(field);
                this.removeFieldResizable(field);
                field.classList.remove("edit-mode");
            });
            
            this.saveFieldPositions();
            this.hideEditInstructions();
        }
    }
    
    makeFieldDraggable(field) {
        field.style.cursor = "move";
        
        const onMouseDown = (e) => {
            if (e.target.classList.contains("field-input")) return;
            
            this.isDragging = true;
            this.dragElement = field;
            
            const rect = field.getBoundingClientRect();
            const canvasRect = this.canvas.getBoundingClientRect();
            
            this.dragOffset = {
                x: e.clientX - rect.left,
                y: e.clientY - rect.top
            };
            
            document.addEventListener("mousemove", onMouseMove);
            document.addEventListener("mouseup", onMouseUp);
            
            e.preventDefault();
        };
        
        const onMouseMove = (e) => {
            if (!this.isDragging || !this.dragElement) return;
            
            const canvasRect = this.canvas.getBoundingClientRect();
            const newX = e.clientX - canvasRect.left - this.dragOffset.x;
            const newY = e.clientY - canvasRect.top - this.dragOffset.y;
            
            // التأكد من أن الحقل يبقى داخل حدود الـ canvas
            const maxX = this.canvas.width - this.dragElement.offsetWidth;
            const maxY = this.canvas.height - this.dragElement.offsetHeight;
            
            const constrainedX = Math.max(0, Math.min(newX, maxX));
            const constrainedY = Math.max(0, Math.min(newY, maxY));
            
            this.dragElement.style.left = constrainedX + "px";
            this.dragElement.style.top = constrainedY + "px";
        };
        
        const onMouseUp = () => {
            this.isDragging = false;
            this.dragElement = null;
            
            document.removeEventListener("mousemove", onMouseMove);
            document.removeEventListener("mouseup", onMouseUp);
        };
        
        field.addEventListener("mousedown", onMouseDown);
        field._dragHandler = onMouseDown;
    }
    
    removeFieldDraggable(field) {
        field.style.cursor = "pointer";
        if (field._dragHandler) {
            field.removeEventListener("mousedown", field._dragHandler);
            delete field._dragHandler;
        }
    }
    
    makeFieldResizable(field) {
        // إضافة مقابض تغيير الحجم
        const resizeHandles = ["nw", "ne", "sw", "se"];
        
        resizeHandles.forEach(direction => {
            const handle = document.createElement("div");
            handle.className = `resize-handle resize-${direction}`;
            handle.style.cssText = `
                position: absolute;
                width: 8px;
                height: 8px;
                background: #007bff;
                border: 1px solid white;
                cursor: ${direction}-resize;
                z-index: 1000;
            `;
            
            // تحديد موقع المقبض
            switch (direction) {
                case "nw":
                    handle.style.top = "-4px";
                    handle.style.left = "-4px";
                    break;
                case "ne":
                    handle.style.top = "-4px";
                    handle.style.right = "-4px";
                    break;
                case "sw":
                    handle.style.bottom = "-4px";
                    handle.style.left = "-4px";
                    break;
                case "se":
                    handle.style.bottom = "-4px";
                    handle.style.right = "-4px";
                    break;
            }
            
            field.appendChild(handle);
        });
    }
    
    removeFieldResizable(field) {
        const handles = field.querySelectorAll(".resize-handle");
        handles.forEach(handle => handle.remove());
    }
    
    addKeyboardShortcuts() {
        document.addEventListener("keydown", (e) => {
            // Ctrl+S للحفظ
            if (e.ctrlKey && e.key === "s") {
                e.preventDefault();
                this.saveFieldData();
            }
            
            // Ctrl+P للطباعة
            if (e.ctrlKey && e.key === "p") {
                e.preventDefault();
                this.printDocument();
            }
            
            // Ctrl+Z للتراجع
            if (e.ctrlKey && e.key === "z" && !e.shiftKey) {
                e.preventDefault();
                this.undo();
            }
            
            // Ctrl+Y أو Ctrl+Shift+Z للإعادة
            if (e.ctrlKey && (e.key === "y" || (e.key === "z" && e.shiftKey))) {
                e.preventDefault();
                this.redo();
            }
            
            // Escape للخروج من وضع التحرير
            if (e.key === "Escape" && this.isEditMode) {
                this.toggleEditMode();
            }
        });
    }
    
    addContextMenu() {
        document.addEventListener("contextmenu", (e) => {
            if (e.target.closest(".text-field-overlay")) {
                e.preventDefault();
                this.showContextMenu(e);
            }
        });
        
        // إخفاء القائمة عند النقر في مكان آخر
        document.addEventListener("click", () => {
            this.hideContextMenu();
        });
    }
    
    showContextMenu(e) {
        this.hideContextMenu();
        
        const menu = document.createElement("div");
        menu.className = "context-menu";
        menu.style.cssText = `
            position: fixed;
            top: ${e.clientY}px;
            left: ${e.clientX}px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 10000;
            min-width: 150px;
        `;
        
        const menuItems = [
            { text: "نسخ", icon: "fas fa-copy", action: () => this.copyField(e.target.closest(".text-field-overlay")) },
            { text: "حذف", icon: "fas fa-trash", action: () => this.deleteField(e.target.closest(".text-field-overlay")) },
            { text: "خصائص", icon: "fas fa-cog", action: () => this.showFieldProperties(e.target.closest(".text-field-overlay")) }
        ];
        
        menuItems.forEach(item => {
            const menuItem = document.createElement("div");
            menuItem.style.cssText = `
                padding: 8px 12px;
                cursor: pointer;
                border-bottom: 1px solid #eee;
                display: flex;
                align-items: center;
                gap: 8px;
            `;
            menuItem.innerHTML = `<i class=\"${item.icon}\"></i> ${item.text}`;
            
            menuItem.addEventListener("click", (e) => {
                e.stopPropagation();
                item.action();
                this.hideContextMenu();
            });
            
            menuItem.addEventListener("mouseenter", () => {
                menuItem.style.backgroundColor = "#f8f9fa";
            });
            
            menuItem.addEventListener("mouseleave", () => {
                menuItem.style.backgroundColor = "white";
            });
            
            menu.appendChild(menuItem);
        });
        
        document.body.appendChild(menu);
        menu.id = "contextMenu";
    }
    
    hideContextMenu() {
        const menu = document.getElementById("contextMenu");
        if (menu) {
            menu.remove();
        }
    }
    
    addAutoSave() {
        // حفظ تلقائي كل 30 ثانية
        setInterval(() => {
            if (Object.keys(this.fieldData).length > 0) {
                this.saveFieldData(true); // حفظ صامت
            }
        }, 30000);
        
        // حفظ عند مغادرة الصفحة
        window.addEventListener("beforeunload", (e) => {
            if (Object.keys(this.fieldData).length > 0) {
                this.saveFieldData(true);
            }
        });
    }
    
    addFieldValidation() {
        // إضافة تحقق من صحة البيانات
        document.addEventListener("input", (e) => {
            if (e.target.classList.contains("field-input")) {
                this.validateField(e.target);
            }
        });
    }
    
    validateField(input) {
        const fieldType = input.type;
        const value = input.value;
        const isRequired = input.required;
        
        // إزالة الرسائل السابقة
        this.clearFieldError(input);
        
        // التحقق من الحقول المطلوبة
        if (isRequired && !value.trim()) {
            this.showFieldError(input, "هذا الحقل مطلوب");
            return false;
        }
        
        // التحقق من نوع البيانات
        switch (fieldType) {
            case "email":
                if (value && !this.isValidEmail(value)) {
                    this.showFieldError(input, "يرجى إدخال بريد إلكتروني صحيح");
                    return false;
                }
                break;
            case "number":
                if (value && isNaN(value)) {
                    this.showFieldError(input, "يرجى إدخال رقم صحيح");
                    return false;
                }
                break;
            case "date":
                if (value && !this.isValidDate(value)) {
                    this.showFieldError(input, "يرجى إدخال تاريخ صحيح");
                    return false;
                }
                break;
        }
        
        return true;
    }
    
    showFieldError(input, message) {
        const field = input.closest(".text-field-overlay");
        field.classList.add("field-error");
        
        const errorDiv = document.createElement("div");
        errorDiv.className = "field-error-message";
        errorDiv.style.cssText = `
            position: absolute;
            bottom: -25px;
            left: 0;
            background: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            white-space: nowrap;
            z-index: 1000;
        `;
        errorDiv.textContent = message;
        
        field.appendChild(errorDiv);
    }
    
    clearFieldError(input) {
        const field = input.closest(".text-field-overlay");
        field.classList.remove("field-error");
        
        const errorMessage = field.querySelector(".field-error-message");
        if (errorMessage) {
            errorMessage.remove();
        }
    }
    
    isValidEmail(email) {
        const emailRegex = /^\S+@\S+\.\S+$/;
        return emailRegex.test(email);
    }
    
    isValidDate(date) {
        const dateObj = new Date(date);
        return dateObj instanceof Date && !isNaN(dateObj);
    }
    
    saveFieldPositions() {
        const fields = document.querySelectorAll(".text-field-overlay");
        const positions = {};
        
        fields.forEach(field => {
            const fieldId = field.dataset.fieldId;
            const rect = field.getBoundingClientRect();
            const canvasRect = this.canvas.getBoundingClientRect();
            
            positions[fieldId] = {
                x: ((rect.left - canvasRect.left) / this.canvas.width) * 100,
                y: ((rect.top - canvasRect.top) / this.canvas.height) * 100,
                width: (rect.width / this.canvas.width) * 100,
                height: (rect.height / this.canvas.height) * 100
            };
        });
        
        // إرسال المواقع الجديدة إلى الخادم
        fetch("/api/pdf/update-field-positions", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
            },
            body: JSON.stringify({
                pdf_document_id: currentPdfDocumentId, // **تم التعديل هنا**
                positions: positions
            })
        });
    }
    
    showEditInstructions() {
        const instructions = document.createElement("div");
        instructions.id = "editInstructions";
        instructions.className = "alert alert-info";
        instructions.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1000;
            max-width: 300px;
        `;
        instructions.innerHTML = `
            <h6><i class=\"fas fa-info-circle\"></i> وضع التحرير</h6>
            <ul class=\"mb-0\" style=\"font-size: 12px;\">
                <li>اسحب الحقول لتغيير موقعها</li>
                <li>استخدم المقابض لتغيير الحجم</li>
                <li>انقر بالزر الأيمن للخيارات</li>
                <li>اضغط Escape للخروج</li>
            </ul>
        `;
        
        document.body.appendChild(instructions);
        
        setTimeout(() => {
            instructions.style.opacity = "0.8";
        }, 100);
    }
    
    hideEditInstructions() {
        const instructions = document.getElementById("editInstructions");
        if (instructions) {
            instructions.remove();
        }
    }
    
    async saveFieldData(silent = false) {
        // التحقق من صحة جميع الحقول
        const inputs = document.querySelectorAll(".field-input");
        let isValid = true;
        
        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });
        
        if (!isValid && !silent) {
            alert("يرجى تصحيح الأخطاء قبل الحفظ");
            return;
        }
        
        try {
            const response = await fetch(saveFieldDataUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector("meta[name=\"csrf-token\"]").getAttribute("content")
                },
                body: JSON.stringify({
                    field_data: this.fieldData
                })
            });
            
            if (response.ok) {
                if (!silent) {
                    this.showSuccessMessage();
                }
                
                // إضافة إلى التاريخ
                this.addToHistory();
            } else {
                throw new Error("فشل في حفظ البيانات");
            }
        } catch (error) {
            console.error("خطأ في حفظ البيانات:", error);
            if (!silent) {
                alert("حدث خطأ في حفظ البيانات");
            }
        }
    }
    
    addToHistory() {
        // إضافة الحالة الحالية إلى التاريخ
        this.history = this.history.slice(0, this.historyIndex + 1);
        this.history.push(JSON.parse(JSON.stringify(this.fieldData)));
        this.historyIndex++;
        
        // الاحتفاظ بآخر 50 حالة فقط
        if (this.history.length > 50) {
            this.history.shift();
            this.historyIndex--;
        }
    }
    
    undo() {
        if (this.historyIndex > 0) {
            this.historyIndex--;
            this.fieldData = JSON.parse(JSON.stringify(this.history[this.historyIndex]));
            this.updateFieldsFromData();
        }
    }
    
    redo() {
        if (this.historyIndex < this.history.length - 1) {
            this.historyIndex++;
            this.fieldData = JSON.parse(JSON.stringify(this.history[this.historyIndex]));
            this.updateFieldsFromData();
        }
    }
    
    updateFieldsFromData() {
        Object.keys(this.fieldData).forEach(fieldName => {
            const input = document.querySelector(`[data-field-name="${fieldName}"]`);
            if (input) {
                input.value = this.fieldData[fieldName];
            }
        });
    }
}

// استبدال الكلاس الأساسي بالكلاس المتقدم
document.addEventListener("DOMContentLoaded", () => {
    new AdvancedPdfViewer();
});
