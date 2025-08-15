import { useEffect } from 'react';

const Modal = ({ title, isOpen, onConfirm, onCancel, children }) => {
  if (!isOpen) return null;

  return (
    <div 
      className="k2 modal-overlay"
    >
      <div 
        className="modal-body"
        onClick={(e) => e.stopPropagation()} // Prevent closing when clicking inside modal
      >

        <h3 class="modal-title">{title}</h3>
        
        {/* Close button */}
        <button
          onClick={onCancel}
          className="modal-close-btn"
          aria-label="Close modal"
        >
          ×
        </button>

        {/* Modal content */}
        <div class="modal-content">
          {children}
        </div>

        {/* Action buttons */}
        <div className="modal-actions">
          <button
            onClick={onConfirm}
            className="k2 modal-btn modal-btn-confirm"
          >
            Proceed to pay
          </button>
        </div>
        <div className="modal-footer">
          Powered by <img src={window.KKWooData.k2_logo_with_name_img} alt='Kopo Kopo (Logo)' /> 
        </div>
      </div>
    </div>
  );
};

export default Modal;
