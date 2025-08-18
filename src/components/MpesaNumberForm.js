export const MpesaNumberForm = ({ onCancel, onConfirm }) => {
  return (
    <>
      <header>
        <h3 class="modal-title">Lipa na M-PESA</h3 >
        
        <button
          onClick={onCancel}
          className="modal-close-btn"
          aria-label="Close modal"
        >
          ×
        </button>
      </header>
      
      <div class='amount-card'>
        <div class='label'>
          Amount to pay
        </div>
        <div class='amount'>
          Ksh 32,000
        </div>
      </div>
      <form>
        <div class='form-group'>
          <label>Enter M-PESA phone number</label>
          <div class='amount-input'>
            <span class='country-code'>
              <img src={window.KKWooData.kenyan_flag_img} alt='Kenyan flag' class='k2'/> 
              <span> +254</span>
            </span>
            <input type='text' placeholder='7xx xxx xxx'/>
          </div>
        </div>
      </form>

      <div className="modal-actions">
        <button
          onClick={onConfirm}
          className="k2 modal-btn modal-btn-confirm"
        >
          Proceed to pay
        </button>
      </div>
    </>
  );
}
