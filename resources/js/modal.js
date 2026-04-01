window.submitModalForm = function(buttonElement) {
    const form = buttonElement.closest('form');
    const formData = new FormData(form);
    
    // Визуальный фидбек
    buttonElement.disabled = true;
    const originalText = buttonElement.innerHTML;
    buttonElement.innerHTML = '<span class="inline-flex items-center justify-center">PROCESSING...</span>';

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: { 
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(async response => {
        const data = await response.json();
        
        if (response.ok && data && data.success) {
            // OK: close modal and refresh calendar or page
            window.dispatchEvent(new CustomEvent('close-modal'));
            
            // If calendar exists, refetch events, otherwise reload page
            if (window.calendar) {
                window.calendar.refetchEvents();
            } else {
                window.location.reload();
            }
        } else {
            // ERROR: show error message from server or generic one
            window.dispatchEvent(new CustomEvent('set-error', { 
                detail: { message: data.message || 'Validation error' } 
            }));
            
            // Return the button text, as .finally will execute later
            buttonElement.disabled = false;
        }
        buttonElement.innerHTML = originalText;
    })
    .catch(err => {
        console.error('Fetch Error:', err);
        window.dispatchEvent(new CustomEvent('set-error', { 
            detail: { message: 'Network or Server Error' } 
        }));
        buttonElement.disabled = false;
        buttonElement.innerHTML = originalText;
    });
}
