(function () {
  'use strict';
  Drupal.bc_dc = Drupal.bc_dc || {};
  Drupal.behaviors.piechartTweak = {
    attach: function (context, settings) {
      document.querySelectorAll('.charts-billboard').forEach(function (el) {
        el.addEventListener('drupalChartsConfigsInitialization', function (e) {
          let detail = e.detail;
          const id = detail.drupalChartDivId;

          // Change the label shown on the pie-slice to be the record-count
          // instead of the percentage.
          detail.pie = detail.pie || {};
          detail.pie.label = detail.pie.label || {};
          detail.pie.label.format = function(value, ratio, id) { 
            return value; 
          } 

          // Change the tooltip, so that it also includes the record-count, added 
          // in with what was there before (data-point name, colour, and percentage).
          detail.tooltip = detail.tooltip || {};
          detail.tooltip.contents = function(d, defaultTitleFormat, defaultValueFormat, color) {
            // d: data for the hovered point(s)
            return `
              &nbsp;
              <table class="bb-tooltip">
                <tbody>
                  <tr class="bb-tooltip-name-${d[0].id}">
                    <td class="name">
                      <span style="background-color:${color(d[0].id)}"></span>${d[0].name}
                    </td>
                    <td class='value'>
                      ${d[0].value} 
                      ${(d[0].value == 1 ? "record" : "records")}
                      (${(Math.round(d[0].ratio * 1000) / 10)}%)
                    </td>
                  </tr>
                </tbody>
              </table>
            `;
          }
        });
      });
    }
  };
}());