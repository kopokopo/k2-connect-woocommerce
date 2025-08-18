import { useEffect } from 'react';

export const Modal = ({ isOpen, children }) => {
  if (!isOpen) return null;

  return (
    <div 
      className="k2 modal-overlay"
    >
      <div 
        className="modal-body"
        onClick={(e) => e.stopPropagation()} // Prevent closing when clicking inside modal
      >
        {/* Modal content */}
        <div class="modal-content">
          {children}
        </div>

        <div className="modal-footer">
          Powered by <img src={window.KKWooData.k2_logo_with_name_img} alt='Kopo Kopo (Logo)' /> 
        </div>
      </div>
    </div>
  );
};
