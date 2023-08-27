<?php if (isset($colors['primary'])):?>
.drts .<?php echo DRTS_BS_PREFIX;?>btn-primary,
.drts .<?php echo DRTS_BS_PREFIX;?>btn-primary.<?php echo DRTS_BS_PREFIX;?>disabled,.drts  .<?php echo DRTS_BS_PREFIX;?>btn-primary:disabled {
  color: #fff;
  background-color: <?php echo $colors['primary'][0];?>;
  border-color: <?php echo $colors['primary'][0];?>;
}
.drts .<?php echo DRTS_BS_PREFIX;?>btn-primary:hover {
  color: #fff;
  background-color: <?php echo $colors['primary'][-7];?>;
  border-color: <?php echo $colors['primary'][-9];?>;
}
.drts .<?php echo DRTS_BS_PREFIX;?>btn-primary:focus,.drts  .<?php echo DRTS_BS_PREFIX;?>btn-primary.<?php echo DRTS_BS_PREFIX;?>focus {
  color: #fff;
  background-color: <?php echo $colors['primary'][-7];?>;
  border-color: <?php echo $colors['primary'][-9];?>;
  box-shadow: 0 0 0 0.2rem rgba(<?php echo implode(', ', $colors['primary']['rgb'][7]);?>, 0.5);
}
.drts .<?php echo DRTS_BS_PREFIX;?>btn-primary:not(:disabled):not(.<?php echo DRTS_BS_PREFIX;?>disabled):active,.drts  .<?php echo DRTS_BS_PREFIX;?>btn-primary:not(:disabled):not(.<?php echo DRTS_BS_PREFIX;?>disabled).<?php echo DRTS_BS_PREFIX;?>active,.drts
.<?php echo DRTS_BS_PREFIX;?>show > .<?php echo DRTS_BS_PREFIX;?>btn-primary.<?php echo DRTS_BS_PREFIX;?>dropdown-toggle {
  color: #fff;
  background-color: <?php echo $colors['primary'][-9];?>;
  border-color: <?php echo $colors['primary'][12];?>;
}
.drts .<?php echo DRTS_BS_PREFIX;?>btn-primary:not(:disabled):not(.<?php echo DRTS_BS_PREFIX;?>disabled):active:focus,.drts  .<?php echo DRTS_BS_PREFIX;?>btn-primary:not(:disabled):not(.<?php echo DRTS_BS_PREFIX;?>disabled).<?php echo DRTS_BS_PREFIX;?>active:focus,.drts
.<?php echo DRTS_BS_PREFIX;?>show > .<?php echo DRTS_BS_PREFIX;?>btn-primary.<?php echo DRTS_BS_PREFIX;?>dropdown-toggle:focus {
  box-shadow: 0 0 0 0.2rem rgba(<?php echo implode(', ', $colors['primary']['rgb'][7]);?>, 0.5);
}
.drts .<?php echo DRTS_BS_PREFIX;?>btn-outline-primary {
  color: <?php echo $colors['primary'][0];?>;
  border-color: <?php echo $colors['primary'][0];?>;
}
.drts .<?php echo DRTS_BS_PREFIX;?>btn-outline-primary:hover,
.drts .<?php echo DRTS_BS_PREFIX;?>btn-outline-primary:not(:disabled):not(.<?php echo DRTS_BS_PREFIX;?>disabled):active,.drts  .<?php echo DRTS_BS_PREFIX;?>btn-outline-primary:not(:disabled):not(.<?php echo DRTS_BS_PREFIX;?>disabled).<?php echo DRTS_BS_PREFIX;?>active,.drts
.<?php echo DRTS_BS_PREFIX;?>show > .<?php echo DRTS_BS_PREFIX;?>btn-outline-primary.<?php echo DRTS_BS_PREFIX;?>dropdown-toggle {
  color: #fff;
  background-color: <?php echo $colors['primary'][0];?>;
  border-color: <?php echo $colors['primary'][0];?>;
}
.drts .<?php echo DRTS_BS_PREFIX;?>btn-outline-primary:focus,.drts  .<?php echo DRTS_BS_PREFIX;?>btn-outline-primary.<?php echo DRTS_BS_PREFIX;?>focus,
.drts .<?php echo DRTS_BS_PREFIX;?>btn-outline-primary:not(:disabled):not(.<?php echo DRTS_BS_PREFIX;?>disabled):active:focus,.drts  .<?php echo DRTS_BS_PREFIX;?>btn-outline-primary:not(:disabled):not(.<?php echo DRTS_BS_PREFIX;?>disabled).<?php echo DRTS_BS_PREFIX;?>active:focus,.drts
.<?php echo DRTS_BS_PREFIX;?>show > .<?php echo DRTS_BS_PREFIX;?>btn-outline-primary.<?php echo DRTS_BS_PREFIX;?>dropdown-toggle:focus {
  box-shadow: 0 0 0 0.2rem rgba(<?php echo implode(', ', $colors['primary']['rgb'][0]);?>, 0.5);
}
.drts .<?php echo DRTS_BS_PREFIX;?>btn-outline-primary.<?php echo DRTS_BS_PREFIX;?>disabled,.drts  .<?php echo DRTS_BS_PREFIX;?>btn-outline-primary:disabled {
  color: <?php echo $colors['primary'][0];?>;
}
.drts .<?php echo DRTS_BS_PREFIX;?>btn-link {
  color: <?php echo $colors['primary'][0];?>;
}
.drts .<?php echo DRTS_BS_PREFIX;?>btn-link:hover {
  color: <?php echo $colors['primary'][-15];?>;
}
.drts .<?php echo DRTS_BS_PREFIX;?>list-group-item.<?php echo DRTS_BS_PREFIX;?>active,
.drts .<?php echo DRTS_BS_PREFIX;?>custom-control-input:checked~.<?php echo DRTS_BS_PREFIX;?>custom-control-label::before {
  color: #fff;
  background-color: <?php echo $colors['primary'][0];?>;
  border-color: <?php echo $colors['primary'][0];?>
    }
    .drts .irs-from::before,.drts .irs-single::before,.drts .irs-to::before,
    .drts .irs-from::after,.drts .irs-single::after,.drts .irs-to::after {
    border-top-color: <?php echo $colors['primary'][0];?>
    }
    .drts .irs--flat .irs-from,.drts .irs--flat .irs-single,.drts .irs--flat .irs-to,
    .drts .irs--flat .irs-bar,.drts .irs--flat .irs-handle > i:first-child {
    background-color: <?php echo $colors['primary'][0];?>;
}
.drts .<?php echo DRTS_BS_PREFIX;?>dropdown-item.<?php echo DRTS_BS_PREFIX;?>active, .drts .<?php echo DRTS_BS_PREFIX;?>dropdown-item:active {
  color: #fff;
  background-color: <?php echo $colors['primary'][0];?>;
}
.drts .<?php echo DRTS_BS_PREFIX;?>form-control:focus {
  color: #495057;
  background-color: #fff;
  border-color: <?php echo $colors['primary'][25];?>;
  box-shadow: 0 0 0 0.2rem rgb(<?php echo implode(' ', $colors['primary']['rgb'][0]);?> / 25%);
}
.drts .<?php echo DRTS_BS_PREFIX;?>btn-primary.<?php echo DRTS_BS_PREFIX;?>focus, .drts .<?php echo DRTS_BS_PREFIX;?>btn-primary:focus {
  box-shadow: 0 0 0 0.2rem rgb(<?php echo implode(' ', $colors['primary']['rgb'][0]);?> / 25%);
}
.drts .<?php echo DRTS_BS_PREFIX;?>bg-primary {
background-color: <?php echo $colors['primary'][0];?> !important;
}
.drts a.<?php echo DRTS_BS_PREFIX;?>bg-primary:hover,.drts  a.<?php echo DRTS_BS_PREFIX;?>bg-primary:focus,.drts
  button.<?php echo DRTS_BS_PREFIX;?>bg-primary:hover,.drts
  button.<?php echo DRTS_BS_PREFIX;?>bg-primary:focus {
  background-color: <?php echo $colors['primary'][-9];?> !important;
}
<?php endif;?>
<?php if (isset($colors['secondary'])):?>
.drts .<?php echo DRTS_BS_PREFIX;?>btn-secondary,
.drts .<?php echo DRTS_BS_PREFIX;?>btn-secondary.<?php echo DRTS_BS_PREFIX;?>disabled,.drts  .<?php echo DRTS_BS_PREFIX;?>btn-secondary:disabled {
  color: #fff;
  background-color: <?php echo $colors['secondary'][0];?>;
  border-color: <?php echo $colors['secondary'][0];?>;
}
.drts .<?php echo DRTS_BS_PREFIX;?>btn-secondary:hover {
  color: #fff;
  background-color: <?php echo $colors['secondary'][-7];?>;
  border-color: <?php echo $colors['secondary'][-9];?>;
}
.drts .<?php echo DRTS_BS_PREFIX;?>btn-secondary:focus,.drts  .<?php echo DRTS_BS_PREFIX;?>btn-secondary.<?php echo DRTS_BS_PREFIX;?>focus {
  color: #fff;
  background-color: <?php echo $colors['secondary'][-7];?>;
  border-color: <?php echo $colors['secondary'][-9];?>;
  box-shadow: 0 0 0 0.2rem rgba(<?php echo implode(', ', $colors['secondary']['rgb'][7]);?>, 0.5);
}
.drts .<?php echo DRTS_BS_PREFIX;?>btn-secondary:not(:disabled):not(.<?php echo DRTS_BS_PREFIX;?>disabled):active,.drts  .<?php echo DRTS_BS_PREFIX;?>btn-secondary:not(:disabled):not(.<?php echo DRTS_BS_PREFIX;?>disabled).<?php echo DRTS_BS_PREFIX;?>active,.drts
.<?php echo DRTS_BS_PREFIX;?>show > .<?php echo DRTS_BS_PREFIX;?>btn-secondary.<?php echo DRTS_BS_PREFIX;?>dropdown-toggle {
  color: #fff;
  background-color: <?php echo $colors['secondary'][-9];?>;
  border-color: <?php echo $colors['secondary'][-12];?>;
}
.drts .<?php echo DRTS_BS_PREFIX;?>btn-secondary:not(:disabled):not(.<?php echo DRTS_BS_PREFIX;?>disabled):active:focus,.drts  .<?php echo DRTS_BS_PREFIX;?>btn-secondary:not(:disabled):not(.<?php echo DRTS_BS_PREFIX;?>disabled).<?php echo DRTS_BS_PREFIX;?>active:focus,.drts
.<?php echo DRTS_BS_PREFIX;?>show > .<?php echo DRTS_BS_PREFIX;?>btn-secondary.<?php echo DRTS_BS_PREFIX;?>dropdown-toggle:focus {
  box-shadow: 0 0 0 0.2rem rgba(<?php echo implode(', ', $colors['secondary']['rgb'][7]);?>, 0.5);
}
.drts .<?php echo DRTS_BS_PREFIX;?>btn-outline-secondary {
  color: <?php echo $colors['secondary'][0];?>;
  border-color: <?php echo $colors['secondary'][0];?>;
}
.drts .<?php echo DRTS_BS_PREFIX;?>btn-outline-secondary:hover,
.drts .<?php echo DRTS_BS_PREFIX;?>btn-outline-secondary:not(:disabled):not(.<?php echo DRTS_BS_PREFIX;?>disabled):active,.drts  .<?php echo DRTS_BS_PREFIX;?>btn-outline-secondary:not(:disabled):not(.<?php echo DRTS_BS_PREFIX;?>disabled).<?php echo DRTS_BS_PREFIX;?>active,.drts
.<?php echo DRTS_BS_PREFIX;?>show > .<?php echo DRTS_BS_PREFIX;?>btn-outline-secondary.<?php echo DRTS_BS_PREFIX;?>dropdown-toggle {
  color: #fff;
  background-color: <?php echo $colors['secondary'][0];?>;
  border-color: <?php echo $colors['secondary'][0];?>;
}
.drts .<?php echo DRTS_BS_PREFIX;?>btn-outline-secondary:focus,.drts  .<?php echo DRTS_BS_PREFIX;?>btn-outline-secondary.<?php echo DRTS_BS_PREFIX;?>focus,
.drts .<?php echo DRTS_BS_PREFIX;?>btn-outline-secondary:not(:disabled):not(.<?php echo DRTS_BS_PREFIX;?>disabled):active:focus,.drts  .<?php echo DRTS_BS_PREFIX;?>btn-outline-secondary:not(:disabled):not(.<?php echo DRTS_BS_PREFIX;?>disabled).<?php echo DRTS_BS_PREFIX;?>active:focus,.drts
.<?php echo DRTS_BS_PREFIX;?>show > .<?php echo DRTS_BS_PREFIX;?>btn-outline-secondary.<?php echo DRTS_BS_PREFIX;?>dropdown-toggle:focus {
  box-shadow: 0 0 0 0.2rem rgba(<?php echo implode(', ', $colors['secondary']['rgb'][0]);?>, 0.5);
}
.drts .<?php echo DRTS_BS_PREFIX;?>btn-outline-secondary.<?php echo DRTS_BS_PREFIX;?>disabled,.drts  .<?php echo DRTS_BS_PREFIX;?>btn-outline-secondary:disabled {
  color: <?php echo $colors['secondary'][0];?>;
}
.drts .<?php echo DRTS_BS_PREFIX;?>btn-link:disabled,.drts  .<?php echo DRTS_BS_PREFIX;?>btn-link.<?php echo DRTS_BS_PREFIX;?>disabled {
  color: <?php echo $colors['secondary'][0];?>;
}
.drts .<?php echo DRTS_BS_PREFIX;?>bg-secondary {
  background-color: <?php echo $colors['secondary'][0];?> !important;
}
.drts a.<?php echo DRTS_BS_PREFIX;?>bg-secondary:hover,.drts  a.<?php echo DRTS_BS_PREFIX;?>bg-secondary:focus,.drts
  button.<?php echo DRTS_BS_PREFIX;?>bg-secondary:hover,.drts
  button.<?php echo DRTS_BS_PREFIX;?>bg-secondary:focus {
  background-color: <?php echo $colors['secondary'][-9];?> !important;
}
.drts .<?php echo DRTS_BS_PREFIX;?>border-primary {
  border-color: <?php echo $colors['primary'][0];?> !important;
}
.drts .<?php echo DRTS_BS_PREFIX;?>border-secondary {
  border-color: <?php echo $colors['secondary'][0];?> !important;
}
<?php endif;?>