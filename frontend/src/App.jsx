import { Routes, Route } from 'react-router-dom';
import AppLayout from './components/AppLayout';
import HomePage from './pages/HomePage';
import TiendaPage from './pages/TiendaPage';
import ProductDetailPage from './pages/ProductDetailPage';
import CartPage from './pages/CartPage';
import CheckoutPage from './pages/CheckoutPage';
import ConfirmationPage from './pages/ConfirmationPage';
import RatingsPage from './pages/RatingsPage';
import ContactPage from './pages/ContactPage';
import AboutPage from './pages/AboutPage';
import WishlistPage from './pages/WishlistPage';
import PolicyPage from './pages/PolicyPage';
import WelcomePage from './pages/WelcomePage';
import AdminPage from './pages/AdminPage';

function App() {
  return (
    <AppLayout>
      <Routes>
        <Route path="/" element={<HomePage />} />
        <Route path="/tienda" element={<TiendaPage />} />
        <Route path="/producto/:id" element={<ProductDetailPage />} />
        <Route path="/carrito" element={<CartPage />} />
        <Route path="/checkout" element={<CheckoutPage />} />
        <Route path="/confirmacion/:id" element={<ConfirmationPage />} />
        <Route path="/valoraciones" element={<RatingsPage />} />
        <Route path="/contacto" element={<ContactPage />} />
        <Route path="/sobre-nosotros" element={<AboutPage />} />
        <Route path="/lista-deseos" element={<WishlistPage />} />
        <Route path="/politica" element={<PolicyPage />} />
        <Route path="/bienvenida" element={<WelcomePage />} />
        <Route path="/admin" element={<AdminPage />} />
      </Routes>
    </AppLayout>
  );
}

export default App;
