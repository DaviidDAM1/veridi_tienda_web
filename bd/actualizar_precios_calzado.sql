USE veridi;

UPDATE productos
SET precio = 64.90
WHERE nombre IN (
  'Zapatillas Running Azul',
  'Zapatillas Running Blancas',
  'Zapatillas Running Negras'
);

UPDATE productos
SET precio = 90.00
WHERE nombre IN (
  'Zapatillas Urban Negras',
  'Zapatillas Urban Blancas'
);

UPDATE productos
SET precio = 100.00
WHERE nombre IN (
  'Zapatillas TN Blancas',
  'Zapatillas TN Negras'
);
