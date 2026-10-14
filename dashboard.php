<?php
// 1. Incluimos el guardián de sesión y funciones de rol
include 'auth.php'; 
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Panel de Control | Almacén ERA</title>
    <link rel="stylesheet" href="css/dashboard-style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
      .search-container {
        margin-bottom: 15px; display: flex; align-items: center; background: white;
        padding: 10px 15px; border-radius: 8px; border: 1px solid #e2e8f0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05); width: 350px;
      }
      .search-container i { color: #94a3b8; margin-right: 10px; }
      .search-input { border: none; outline: none; width: 100%; font-size: 0.95rem; color: #334155; }
      
      .user-info { position: relative; cursor: pointer; }
      .user-trigger { display: flex; align-items: center; gap: 10px; }
      .dropdown-menu {
          display: none; position: absolute; top: 100%; right: 0;
          background: white; min-width: 180px; box-shadow: 0 8px 16px rgba(0,0,0,0.1);
          border-radius: 8px; margin-top: 10px; z-index: 1100; border: 1px solid #e2e8f0;
      }
      .dropdown-menu a { display: flex; align-items: center; gap: 10px; padding: 12px 15px; color: #334155; text-decoration: none; font-size: 0.9rem; }
      .dropdown-menu a:hover { background: #f1f5f9; }
      .logout-link { color: #ef4444 !important; }
      
      .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
      .badge.entry { background: #dcfce7; color: #166534; }
      .badge.exit { background: #fee2e2; color: #991b1b; }
    </style>
  </head>
  <body>
    <div class="main-container">
      <nav class="sidebar">
        <div class="sidebar-header">
          <i class="fas fa-warehouse"></i>
          <span>Almacén Era</span>
        </div>
        <ul class="nav-links">
          <li class="active"><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
          <li><a href="entradas.php"><i class="fas fa-arrow-down"></i> Entradas</a></li>
          <li><a href="salidas.php"><i class="fas fa-arrow-up"></i> Salidas</a></li>
          <li><a href="productos.php"><i class="fas fa-boxes-stacked"></i> Productos</a></li>
          
          <?php if (esAdmin()): ?>
            <li><a href="empresas.php"><i class="fas fa-building"></i> Empresas</a></li>
            <li><a href="empleados.php"><i class="fas fa-users"></i> Empleados</a></li>
            <li><a href="reportes.php"><i class="fas fa-file-invoice"></i> Reportes</a></li>
          <?php endif; ?>
        </ul>
      </nav>

      <main class="content">
        <header class="top-bar">
          <h2>Panel Principal - <small style="color:var(--accent)"><?php echo $_SESSION['puesto']; ?></small></h2>
          
          <div class="user-info" id="userDropdown">
            <div class="user-trigger" onclick="toggleUserMenu(event)">
                <span id="displayUserName"><?php echo $_SESSION['nombre']; ?></span>
                <i class="fas fa-user-circle" style="font-size: 1.5rem;"></i>
            </div>
            
            <div class="dropdown-menu" id="dropdownMenu">
                <hr style="margin:0; border:0; border-top:1px solid #eee;">
                <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </div>
          </div>
        </header>

        <section class="stats-grid">
          <div class="stat-card">
            <i class="fas fa-box blue"></i>
            <div><h3 id="stat-stock">...</h3><p>Stock Total</p></div>
          </div>
          <div class="stat-card">
            <i class="fas fa-arrow-trend-up green"></i>
            <div><h3 id="stat-entradas">...</h3><p>Entradas (Hoy)</p></div>
          </div>
          <div class="stat-card">
            <i class="fas fa-arrow-trend-down orange"></i>
            <div><h3 id="stat-salidas">...</h3><p>Salidas (Hoy)</p></div>
          </div>
        </section>

        <div class="actions">
          <button class="btn btn-entry" id="btnEntrada"><i class="fas fa-plus"></i> Registrar Entrada</button>
          <button class="btn btn-exit" id="btnSalida" style="background:#ef4444; color:white;"><i class="fas fa-minus"></i> Registrar Salida</button>
        </div>

        <section class="table-container">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <h3>Movimientos Recientes</h3>
            <div class="search-container">
              <i class="fas fa-search"></i>
              <input type="text" id="searchInput" class="search-input" placeholder="Buscar">
            </div>
          </div>

          <table id="movementsTable">
            <thead>
              <tr>
                <th>Fecha</th><th>Producto</th><th>Tipo</th><th>Cant.</th><th>Empresa</th><th>Importe</th>
              </tr>
            </thead>
            <tbody id="listaMovimientos"></tbody>
          </table>
        </section>
      </main>
    </div>

    <div id="modalEntrada" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3>Nueva Entrada</h3><span class="close-btn" onclick="closeModal('modalEntrada')">&times;</span></div>
            <form id="formEntrada">
                 <div class="form-group"><label>No. Importe</label><input type="text" name="num_importe" class="form-control" required></div>
                 <div class="form-group"><label>Producto</label><select name="id_producto" id="selProdEntrada" class="form-control" required></select></div>
                 <div class="form-group"><label>Empresa</label><select name="id_empresa" id="selEmpEntrada" class="form-control" required></select></div>
                 <div class="form-group"><label>Cantidad</label><input type="number" name="cantidad" class="form-control" min="1" required></div>
                 <button type="submit" class="btn btn-entry" style="width:100%">Guardar Entrada</button>
            </form>
        </div>
    </div>

    <div id="modalSalida" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3>Registrar Salida</h3><span class="close-btn" onclick="closeModal('modalSalida')">&times;</span></div>
            <form id="formSalida">
                 <div class="form-group"><label>No. Importe</label><input type="text" name="num_importe" class="form-control" required></div>
                 <div class="form-group"><label>Producto</label><select name="id_producto" id="selProdSalida" class="form-control" required></select></div>
                 <div class="form-group"><label>Empresa Destino</label><select name="id_empresa" id="selEmpSalida" class="form-control" required></select></div>
                 <div class="form-group"><label>Cantidad</label><input type="number" name="cantidad" class="form-control" min="1" required></div>
                 <button type="submit" class="btn" style="width:100%; background:#ef4444; color:white">Confirmar Salida</button>
            </form>
        </div>
    </div>

    <div id="modalPassword" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h3>Cambiar Contraseña</h3>
                <span class="close-btn" onclick="closeModal('modalPassword')">&times;</span>
            </div>
            <form id="formChangePassword">
                <div class="form-group">
                    <label>Contraseña Actual</label>
                    <input type="password" name="old_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Nueva Contraseña</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-entry" style="width:100%">Actualizar Contraseña</button>
            </form>
        </div>
    </div>

    <div id="chatbot-container" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
    <button id="chatbot-launcher" style="background: var(--accent); color: white; border: none; padding: 15px; border-radius: 50%; cursor: pointer; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
        <i class="fas fa-robot" style="font-size: 24px;"></i>
    </button>

    <div id="chatbot-window" style="display: none; width: 300px; height: 400px; background: white; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.2); flex-direction: column; overflow: hidden; position: absolute; bottom: 70px; right: 0;">
        <div style="background: var(--accent); color: white; padding: 15px; font-weight: bold; display: flex; justify-content: space-between;">
            <span>Asistente ERA</span>
            <span onclick="toggleChat()" style="cursor:pointer;">&times;</span>
        </div>
        <div id="chatbot-messages" style="flex: 1; padding: 15px; overflow-y: auto; font-size: 0.9rem; background: #f8fafc;">
            <div style="background: #e2e8f0; padding: 8px; border-radius: 8px; margin-bottom: 10px; max-width: 85%;">
                ¡Hola! Soy el bot de Almacén ERA. ¿En qué puedo ayudarte hoy?
            </div>
        </div>
        <div style="padding: 10px; border-top: 1px solid #eee; display: flex; gap: 5px;">
            <input type="text" id="chatbot-input" placeholder="Escribe tu duda..." style="flex: 1; border: 1px solid #ddd; padding: 8px; border-radius: 4px; outline: none;">
            <button id="chatbot-send" style="background: var(--accent); color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer;">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>

    <script>
      const dropdownMenu = document.getElementById('dropdownMenu');

      async function cargarDashData() {
          const resStats = await fetch('acciones_dashboard.php?accion=stats');
          const stats = await resStats.json();
          document.getElementById('stat-stock').innerText = stats.stock || 0;
          document.getElementById('stat-entradas').innerText = stats.entradas || 0;
          document.getElementById('stat-salidas').innerText = stats.salidas || 0;

          const resHist = await fetch('acciones_dashboard.php?accion=historial');
          const historial = await resHist.json();
          document.getElementById('listaMovimientos').innerHTML = historial.map(m => `
              <tr>
                <td>${m.fecha}</td>
                <td>${m.producto}</td>
                <td><span class="badge ${m.tipo_movimiento === 'Entrada' ? 'entry' : 'exit'}">${m.tipo_movimiento}</span></td>
                <td>${m.cantidad}</td>
                <td>${m.nombre_empresa}</td>
                <td>${m.num_importe}</td>
              </tr>
          `).join('');
      }

      async function cargarSelects() {
          const resP = await fetch('acciones_producto.php?accion=leer');
          const prods = await resP.json();
          const optP = prods.map(p => `<option value="${p.id}">${p.nombre}</option>`).join('');
          document.getElementById('selProdEntrada').innerHTML = optP;
          document.getElementById('selProdSalida').innerHTML = optP;

          const resE = await fetch('acciones_empresa.php?accion=leer');
          const emps = await resE.json();
          const optE = emps.map(e => `<option value="${e.id}">${e.nombre_empresa}</option>`).join('');
          document.getElementById('selEmpEntrada').innerHTML = optE;
          document.getElementById('selEmpSalida').innerHTML = optE;
      }

      function openModal(id) {
          if (id !== 'modalPassword') cargarSelects();
          document.getElementById(id).style.display = "flex";
          dropdownMenu.style.display = 'none'; 
      }

      function closeModal(id) { document.getElementById(id).style.display = "none"; }

      function toggleUserMenu(e) {
          if (e) e.stopPropagation();
          dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
      }

      async function procesarMov(e, tipo, modalId) {
          e.preventDefault();
          const formData = new FormData(e.target);
          formData.append('tipo_movimiento', tipo);
          const res = await fetch('acciones_movimientos.php', { method: 'POST', body: formData });
          const r = await res.json();
          if(r.status === 'success') {
              closeModal(modalId);
              cargarDashData();
              e.target.reset();
          } else { alert("Error: " + r.message); }
      }

      document.getElementById('formEntrada').onsubmit = (e) => procesarMov(e, 'Entrada', 'modalEntrada');
      document.getElementById('formSalida').onsubmit = (e) => procesarMov(e, 'Salida', 'modalSalida');
      
      document.getElementById("btnEntrada").onclick = () => openModal("modalEntrada");
      document.getElementById("btnSalida").onclick = () => openModal("modalSalida");

      window.onclick = function(event) {
          if (event.target.className === 'modal') event.target.style.display = "none";
          if (!document.getElementById('userDropdown').contains(event.target)) dropdownMenu.style.display = 'none';
      };

      // BUSCADOR FUNCIONAL
      document.getElementById('searchInput').addEventListener('keyup', function() {
          const filter = this.value.toLowerCase();
          const rows = document.querySelectorAll('#movementsTable tbody tr');
          rows.forEach(row => {
              row.style.display = row.innerText.toLowerCase().includes(filter) ? "" : "none";
          });
      });


      document.getElementById('formChangePassword').onsubmit = async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    try {
        const res = await fetch('acciones_password.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await res.json();
        
        if (result.status === "success") {
            alert("¡Contraseña actualizada correctamente!");
            closeModal('modalPassword');
            e.target.reset();
        } else {
            alert("Error: " + result.message);
        }
    } catch (error) {
        console.error("Error:", error);
        alert("Hubo un error al procesar la solicitud.");
    }
};

      cargarDashData();
    </script>
    <script src="bot.js"></script>
  </body>
</html>