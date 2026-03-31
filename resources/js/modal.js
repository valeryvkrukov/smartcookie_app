window.submitModalForm = function(buttonElement) {
    const form = buttonElement.closest('form');
    const formData = new FormData(form);
    
    // Визуальный фидбек
    buttonElement.disabled = true;
    const originalText = buttonElement.innerHTML; // Используем innerHTML чтобы сохранить стили
    buttonElement.innerHTML = '<span class="inline-flex items-center justify-center"><i class="ti-reload animate-spin mr-3 text-sm flex-shrink-0" style="line-height: 1; display: inline-block;"></i> PROCESSING...</span>';

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
            // УСПЕХ: закрываем и обновляем
            window.dispatchEvent(new CustomEvent('close-modal'));
            
            // Если есть календарь — рефрешим его, если нет — релоад страницы
            if (window.calendar) {
                window.calendar.refetchEvents();
            } else {
                window.location.reload();
            }
        } else {
            // ОШИБКА: выводим сообщение, модалку НЕ закрываем
            window.dispatchEvent(new CustomEvent('set-error', { 
                detail: { message: data.message || 'Validation error' } 
            }));
            
            // Возвращаем текст кнопки, так как .finally выполнится позже
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
