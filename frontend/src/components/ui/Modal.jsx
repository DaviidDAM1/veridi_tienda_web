import React from 'react';
import { createPortal } from 'react-dom';
import './Modal.css';

function Modal({ isOpen, title, message, onClose, buttonText = 'Aceptar' }) {
  if (!isOpen) return null;

  return createPortal(
    <div className="modal-overlay" onClick={(e) => { if (e.target === e.currentTarget) onClose(); }}>
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
    </div>,
    document.body
  );
}

export default Modal;
