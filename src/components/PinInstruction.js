import { Spinner } from './Spinner.js';
import { ReactComponent as PhoneIcon } from '../../images/svg/phone.svg'

export const PinInstruction = ({ onClose }) => {
  return (
    <div id='pin-instruction'>
      <PhoneIcon />
      <div>
        <p className='main-info'>Enter your M-PESA PIN when prompted to complete payment</p>
        <p className='side-note'>Please note the message will come from Kopo Kopo</p>
      </div>

      <Spinner />

      <div className="modal-actions">
        <button
          onClick={onClose}
          className="k2 modal-btn modal-btn-confirm outline w-full"
        >
          Close
        </button>
      </div>
    </div> 
  );
}
