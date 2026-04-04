window.submitModalForm = function(buttonElement) {
    const form = buttonElement.closest('form');
    const formData = new FormData(form);

    // ── Visual feedback: disable button and show loading state
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
            // ── Success: close modal and trigger UI refresh
            buttonElement.disabled = false;
            buttonElement.innerHTML = originalText;

            window.dispatchEvent(new CustomEvent('close-modal'));

            // ── Refresh: refetch calendar events if present, otherwise reload page
            if (window.calendar) {
                window.calendar.refetchEvents();
            } else {
                window.location.reload();
            }
        } else {
            // ── Error: dispatch server message or generic validation fallback
            window.dispatchEvent(new CustomEvent('set-error', {
                detail: { message: data.message || 'Validation error' }
            }));

            // ── Restore: re-enable button
            buttonElement.disabled = false;
            buttonElement.innerHTML = originalText;
        }
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
