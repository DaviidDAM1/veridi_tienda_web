import React from 'react';
import './Modal.css';

function Modal({ isOpen, title, message, onClose, buttonText = 'Aceptar' }) {
  if (!isOpen) return null;

  return (
    <div className="modal-overlay">
      <div className="modal-content">
        <div className="modal-header">
          <h2>{title}</h2>
        </div>
        <div className="modal-body">
          <p>{message}</p>
        </div>
        <div className="modal-footer">
          <button className="modal-button" onClick={onClose}>
            {buttonText}
          </button>
        </div>
      </div>
    </div>
  );
}

export default Modal;
