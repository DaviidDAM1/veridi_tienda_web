import React from 'react';
import { createPortal } from 'react-dom';
import './Modal.css';

function Modal({ isOpen, title, message, onClose, buttonText = 'Aceptar' }) {
  if (!isOpen) return null;

  return createPortal(
    <div className="veridi-modal-overlay" onClick={(e) => { if (e.target === e.currentTarget) onClose(); }}>
      <div className="veridi-modal-content">
        <div className="veridi-modal-header">
          <h2>{title}</h2>
        </div>
        <div className="veridi-modal-body">
          <p>{message}</p>
        </div>
        <div className="veridi-modal-footer">
          <button className="veridi-modal-button" onClick={onClose}>
            {buttonText}
          </button>
        </div>
      </div>
    </div>,
    document.body
  );
}

export default Modal;
