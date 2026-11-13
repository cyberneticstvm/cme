<dialog id="admin-delete-dialog" class="admin-delete-dialog" aria-labelledby="admin-delete-title" aria-describedby="admin-delete-description">
    <div class="admin-delete-dialog-content">
        <button class="admin-delete-close" type="button" data-delete-cancel aria-label="Close delete confirmation"><x-icon name="plus" /></button>
        <span class="admin-delete-emblem" aria-hidden="true"><x-icon name="trash" /></span>
        <p class="admin-eyebrow">REGISTRATION MANAGEMENT</p>
        <h2 id="admin-delete-title">Delete this registration?</h2>
        <p class="admin-delete-intro">Please check the delegate details before continuing.</p>
        <div class="admin-delete-delegate"><span class="admin-delete-avatar" data-delete-initial aria-hidden="true"></span><div><strong data-delete-name></strong><span data-delete-email></span><small data-delete-reference></small></div></div>
        <p class="admin-delete-warning" id="admin-delete-description"><strong>This action cannot be undone.</strong> The delegate&rsquo;s registration details and internal notes will be permanently removed from the registration database.</p>
        <form method="POST" data-delete-form>
            @csrf
            @method('DELETE')
            <input type="hidden" name="confirm_delete" value="1">
            <div class="admin-delete-dialog-actions"><button type="button" class="admin-button admin-button-cancel" data-delete-cancel autofocus>Keep registration</button><button type="submit" class="admin-button admin-button-danger"><x-icon name="trash" /><span data-delete-submit-label>Delete registration</span></button></div>
            <p class="admin-delete-progress" role="status" aria-live="polite" data-delete-progress></p>
        </form>
    </div>
</dialog>
