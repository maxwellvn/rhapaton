document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('rhapathon-form');
    const submitBtn = document.querySelector('.submit-btn');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateForm()) {
            submitBtn.textContent = 'Submitting...';
            submitBtn.disabled = true;
            
            setTimeout(function() {
                alert('Thank you for registering for Rhapathon Wonder Conference! We will contact you soon with more details.');
                form.reset();
                submitBtn.textContent = 'Register for Rhapathon';
                submitBtn.disabled = false;
            }, 2000);
        }
    });
    
    function validateForm() {
        const requiredFields = [
            'fullName',
            'email',
            'phone',
            'participationType',
            'experience'
        ];
        
        let isValid = true;
        
        requiredFields.forEach(function(fieldId) {
            const field = document.getElementById(fieldId);
            const value = field.value.trim();
            
            if (!value) {
                showError(field, 'This field is required');
                isValid = false;
            } else {
                clearError(field);
            }
        });
        
        const email = document.getElementById('email');
        if (email.value && !isValidEmail(email.value)) {
            showError(email, 'Please enter a valid email address');
            isValid = false;
        }
        
        const phone = document.getElementById('phone');
        if (phone.value && !isValidPhone(phone.value)) {
            showError(phone, 'Please enter a valid phone number');
            isValid = false;
        }
        
        const terms = document.getElementById('terms');
        if (!terms.checked) {
            showError(terms, 'You must agree to the terms and conditions');
            isValid = false;
        }
        
        return isValid;
    }
    
    function showError(field, message) {
        clearError(field);
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        errorDiv.style.color = '#dc3545';
        errorDiv.style.fontSize = '14px';
        errorDiv.style.marginTop = '5px';
        
        field.style.borderColor = '#dc3545';
        field.parentNode.appendChild(errorDiv);
    }
    
    function clearError(field) {
        const existingError = field.parentNode.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }
        field.style.borderColor = '#ddd';
    }
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    function isValidPhone(phone) {
        const phoneRegex = /^[\+]?[\d\s\-\(\)]{10,}$/;
        return phoneRegex.test(phone);
    }
    
    const inputs = document.querySelectorAll('input, select, textarea');
    inputs.forEach(function(input) {
        input.addEventListener('blur', function() {
            if (this.hasAttribute('required') && !this.value.trim()) {
                showError(this, 'This field is required');
            } else {
                clearError(this);
            }
        });
        
        input.addEventListener('input', function() {
            if (this.style.borderColor === 'rgb(220, 53, 69)') {
                clearError(this);
            }
        });
    });
    
    const participationType = document.getElementById('participationType');
    participationType.addEventListener('change', function() {
        const speakerFields = document.getElementById('speaker-fields');
        if (this.value === 'speaker') {
            if (!speakerFields) {
                addSpeakerFields();
            }
        } else {
            if (speakerFields) {
                speakerFields.remove();
            }
        }
    });
    
    function addSpeakerFields() {
        const participationSection = document.querySelector('.form-section:nth-child(2)');
        const speakerDiv = document.createElement('div');
        speakerDiv.id = 'speaker-fields';
        speakerDiv.innerHTML = `
            <div class="form-group">
                <label for="presentationTitle">Presentation Title</label>
                <input type="text" id="presentationTitle" name="presentationTitle" placeholder="Title of your presentation">
            </div>
            <div class="form-group">
                <label for="presentationDescription">Presentation Description</label>
                <textarea id="presentationDescription" name="presentationDescription" rows="4" placeholder="Brief description of your presentation..."></textarea>
            </div>
            <div class="form-group">
                <label for="speakerBio">Speaker Bio</label>
                <textarea id="speakerBio" name="speakerBio" rows="3" placeholder="Your professional bio..."></textarea>
            </div>
        `;
        participationSection.appendChild(speakerDiv);
    }
});