@if ($showCreateModal ?? false)
    <div class="crown-modal-backdrop" wire:click.self="closeCreateShipmentModal">
        <div class="crown-modal" wire:click.stop>
            <h3 class="crown-modal__title">حمولة جديدة</h3>
            <label for="sh-name">اسم الحمولة</label>
            <input id="sh-name" type="text" wire:model="newShipmentName" />
            <label for="sh-date">تاريخ التوريد</label>
            <input id="sh-date" type="date" wire:model="newShipmentDate" />
            <label for="sh-driver">السائق</label>
            <input id="sh-driver" type="text" wire:model="newDriverName" />
            <label for="sh-vehicle">رقم السيارة</label>
            <input id="sh-vehicle" type="text" wire:model="newVehicleNo" />
            <label for="sh-responsible">المسؤول</label>
            <input id="sh-responsible" type="text" wire:model="newResponsible" />
            <label for="sh-arrival">موعد الوصول</label>
            <input id="sh-arrival" type="datetime-local" wire:model="newArrivalTime" />
            <label for="sh-notes">ملاحظات</label>
            <textarea id="sh-notes" wire:model="newNotes" rows="2" style="width:100%;margin-bottom:0.75rem;border-radius:0.375rem;border:0.5px solid rgb(var(--gray-300));padding:0.5rem;background:rgb(var(--white));color:rgb(var(--gray-950));"></textarea>
            <div class="crown-modal__actions">
                <button type="button" wire:click="closeCreateShipmentModal" class="crown-btn crown-btn--ghost">إلغاء</button>
                <button type="button" wire:click="createShipment" class="crown-btn crown-btn--primary" wire:loading.attr="disabled" wire:target="createShipment">حفظ وتفعيل</button>
            </div>
        </div>
    </div>
@endif

@if ($showEditModal ?? false)
    <div class="crown-modal-backdrop" wire:click.self="closeEditShipmentModal">
        <div class="crown-modal" wire:click.stop>
            <h3 class="crown-modal__title">تعديل بيانات الحمولة</h3>
            <label for="sh-edit-name">اسم الحمولة</label>
            <input id="sh-edit-name" type="text" wire:model="newShipmentName" />
            <label for="sh-edit-date">تاريخ التوريد</label>
            <input id="sh-edit-date" type="date" wire:model="newShipmentDate" />
            <label for="sh-edit-driver">السائق</label>
            <input id="sh-edit-driver" type="text" wire:model="newDriverName" />
            <label for="sh-edit-vehicle">رقم السيارة</label>
            <input id="sh-edit-vehicle" type="text" wire:model="newVehicleNo" />
            <label for="sh-edit-responsible">المسؤول</label>
            <input id="sh-edit-responsible" type="text" wire:model="newResponsible" />
            <label for="sh-edit-arrival">موعد الوصول</label>
            <input id="sh-edit-arrival" type="datetime-local" wire:model="newArrivalTime" />
            <label for="sh-edit-notes">ملاحظات</label>
            <textarea id="sh-edit-notes" wire:model="newNotes" rows="2" style="width:100%;margin-bottom:0.75rem;border-radius:0.375rem;border:0.5px solid rgb(var(--gray-300));padding:0.5rem;background:rgb(var(--white));color:rgb(var(--gray-950));"></textarea>
            <div class="crown-modal__actions">
                <button type="button" wire:click="closeEditShipmentModal" class="crown-btn crown-btn--ghost">إلغاء</button>
                <button type="button" wire:click="updateActiveShipment" class="crown-btn crown-btn--primary" wire:loading.attr="disabled" wire:target="updateActiveShipment">حفظ</button>
            </div>
        </div>
    </div>
@endif
