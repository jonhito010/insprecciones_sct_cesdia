<?php
/**
 * @var \App\View\AppView $this
 * @var \Cake\ORM\ResultSet|\App\Model\Entity\User[] $users
 */
$this->assign('title', 'Usuarios');
$identity = $this->request->getAttribute('identity');
$miId = $identity ? (int)$identity->getIdentifier() : 0;

$userIniciales = static function (?string $nombre): string {
    $n = trim((string)$nombre);
    if ($n === '') {
        return '?';
    }
    $parts = preg_split('/\s+/u', $n, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $sub = static function (string $s, int $start, int $len): string {
        if (function_exists('mb_substr')) {
            return (string)mb_substr($s, $start, $len);
        }

        return substr($s, $start, $len);
    };
    if (count($parts) >= 2) {
        return strtoupper($sub($parts[0], 0, 1) . $sub($parts[count($parts) - 1], 0, 1));
    }

    return strtoupper($sub($parts[0], 0, min(2, strlen($parts[0]))));
};
?>

<div class="cesdia-page-header">
  <h1>Usuarios del sistema</h1>
  <?= $this->Html->link('Nuevo usuario', ['action' => 'add'], ['class' => 'btn-cesdia btn-cesdia-primary']) ?>
</div>

<div class="cesdia-card cesdia-card--users-table">
  <div class="card-header">
    <span class="card-header-title">
      <svg class="cesdia-card-header-icon" viewBox="0 0 16 16" fill="none" aria-hidden="true">
        <circle cx="8" cy="5" r="3" stroke="currentColor" stroke-width="1.4"/>
        <path d="M2 14c0-3.314 2.686-5 6-5s6 1.686 6 5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
      </svg>
      <span>Cuentas registradas</span>
    </span>
    <span class="cesdia-users-meta"><?= $this->Paginator->counter('{{count}} usuarios') ?></span>
  </div>
  <div class="cesdia-table-wrapper cesdia-table-wrapper--scroll cesdia-table-wrapper--inset">
  <table class="cesdia-table cesdia-table--users">
    <thead>
      <tr>
        <th scope="col">Nombre</th>
        <th scope="col">Correo</th>
        <th scope="col">Rol</th>
        <th scope="col">Técnico vinculado</th>
        <th scope="col" class="cesdia-table-th--activity">Última actividad</th>
        <th scope="col" class="cesdia-table-th--actions">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u) : ?>
      <tr class="<?= (int)$u->id === $miId ? 'cesdia-table-row--self' : '' ?>">
        <td>
          <div class="cesdia-table-usercell">
            <span class="cesdia-table-usercell__avatar" aria-hidden="true"><?= h($userIniciales($u->nombre)) ?></span>
            <div class="cesdia-table-usercell__text">
              <span class="cesdia-table-usercell__name"><?= h($u->nombre) ?></span>
              <?php if ((int)$u->id === $miId) : ?>
                <span class="cesdia-table-usercell__you">Tú</span>
              <?php endif; ?>
            </div>
          </div>
        </td>
        <td>
          <?= $this->Html->link((string)$u->email, 'mailto:' . (string)$u->email, ['class' => 'cesdia-table-email']) ?>
        </td>
        <td>
          <?php if ($u->rol === 'admin') : ?>
            <span class="cesdia-role-tag cesdia-role-tag--admin">Administrador</span>
          <?php else : ?>
            <span class="cesdia-role-tag cesdia-role-tag--tec">Técnico</span>
          <?php endif; ?>
        </td>
        <td><span class="cesdia-table-muted"><?= ($u->rol === 'tecnico' && $u->tecnico && !empty($u->tecnico->nombre)) ? h($u->tecnico->nombre) : '—' ?></span></td>
        <td class="cesdia-table-td--activity">
          <?php
          $ua = $u->ultimo_acceso;
          if ($ua === null) {
              echo '<span class="cesdia-user-sin-actividad">Sin registro</span>';
          } else {
              $min = $ua->diffInMinutes();
              $enLinea = $min <= 5;
              $reciente = !$enLinea && $min <= 20;
              echo '<div class="cesdia-user-actividad">';
              if ($enLinea) {
                  echo '<span class="cesdia-presence cesdia-presence--online" title="Actividad en los últimos 5 minutos (sesión activa o reciente).">';
                  echo '<span class="cesdia-presence__dot" aria-hidden="true"></span>';
                  echo '<span class="cesdia-presence__label">En línea</span>';
                  echo '</span>';
              } elseif ($reciente) {
                  echo '<span class="cesdia-presence cesdia-presence--reciente" title="Conectado hace poco; puede estar ausente unos minutos.">';
                  echo '<span class="cesdia-presence__dot cesdia-presence__dot--reciente" aria-hidden="true"></span>';
                  echo '<span class="cesdia-presence__label">Reciente</span>';
                  echo '</span>';
              }
              echo '<div class="cesdia-user-actividad__fecha">' . h($ua->i18nFormat('dd/MM/yyyy HH:mm'));
              if (!$enLinea && !$reciente && $min < 24 * 60) {
                  echo ' <span class="cesdia-user-actividad__hace">(hace ' . (int)$min . ' min)</span>';
              }
              echo '</div></div>';
          }
          ?>
        </td>
        <td class="cesdia-table-td--actions">
          <div class="cesdia-table-actions">
            <?= $this->Html->link('Editar', ['action' => 'edit', $u->id], ['class' => 'cesdia-table-action cesdia-table-action--edit']) ?>
            <?php if ((int)$u->id !== $miId) : ?>
              <?= $this->Form->postLink(
                  'Eliminar',
                  ['action' => 'delete', $u->id],
                  [
                      'class' => 'cesdia-table-action cesdia-table-action--delete',
                      'confirm' => '¿Eliminar este usuario?',
                  ]
              ) ?>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if ($users->count() === 0) : ?>
      <tr><td colspan="6" class="cesdia-table-empty">No hay usuarios. <?= $this->Html->link('Crear el primero', ['action' => 'add']) ?></td></tr>
      <?php endif; ?>
    </tbody>
  </table>
  </div>

  <?= $this->element('cesdia_pagination', ['counter' => 'Página {{page}} de {{pages}}']) ?>
</div>
