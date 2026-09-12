(function (Icinga, $) {

    'use strict';

    var Hcloud = function (module) {
        this.module = module;

        this.module.on('mousemove', '.hcloud-metric-plot', this.onMove);
        this.module.on('mouseleave', '.hcloud-metric-plot', this.onLeave);
    };

    Hcloud.prototype = {

        onMove: function (event) {
            var $plot = $(event.currentTarget);
            var $svg = $plot.find('.hcloud-metric-chart');
            var $tooltip = $plot.find('.hcloud-metric-tooltip');

            if (! $svg.length || ! $tooltip.length) {
                return;
            }

            var data = readSeries($svg);
            if (data === null || ! data.t.length) {
                return;
            }

            var plot = readPlot($svg);
            if (plot === null) {
                return;
            }

            var box = $svg[0].getBoundingClientRect();
            if (! box.width) {
                return;
            }

            var viewBoxWidth = readViewBoxWidth($svg);
            if (! viewBoxWidth) {
                return;
            }

            var scale = box.width / viewBoxWidth;
            var plotLeft = plot.left * scale;
            var plotWidth = (plot.right - plot.left) * scale;
            var offset = event.clientX - box.left - plotLeft;

            if (offset < 0) {
                offset = 0;
            } else if (offset > plotWidth) {
                offset = plotWidth;
            }

            var ratio = plotWidth > 0 ? offset / plotWidth : 0;
            var index = nearestWithValue(data, Math.round(ratio * (data.t.length - 1)));

            if (index === null) {
                return;
            }

            var snapped = plot.left + (plot.right - plot.left) * (index / Math.max(data.t.length - 1, 1));
            $svg.find('.hcloud-metric-crosshair')
                .attr({x1: snapped, x2: snapped})
                .prop('hidden', false);

            $tooltip.empty();
            $tooltip.append($('<div>').addClass('hcloud-metric-tooltip-time').text(data.t[index]));

            data.s.forEach(function (series, position) {
                var row = $('<div>').addClass('hcloud-metric-tooltip-row');

                row.append($('<span>')
                    .addClass('hcloud-metric-swatch hcloud-metric-series-' + (position % 4)));
                row.append($('<span>').addClass('hcloud-metric-tooltip-label').text(series.l));
                var value = series.v[index];

                row.append($('<span>')
                    .addClass('hcloud-metric-tooltip-value')
                    .text(value === null || typeof value === 'undefined' ? '-' : value));

                $tooltip.append(row);
            });

            $tooltip.prop('hidden', false);

            var tooltipWidth = $tooltip.outerWidth() || 0;
            var left = snapped * scale + 12;

            if (left + tooltipWidth > box.width) {
                left = snapped * scale - tooltipWidth - 12;
            }

            $tooltip.css({left: Math.max(left, 0) + 'px'});
        },

        onLeave: function (event) {
            var $plot = $(event.currentTarget);

            $plot.find('.hcloud-metric-crosshair').prop('hidden', true);
            $plot.find('.hcloud-metric-tooltip').prop('hidden', true).empty();
        }
    };

    function readSeries($svg) {
        var cached = $svg.data('hcloudSeriesParsed');
        if (cached) {
            return cached;
        }

        var raw = $svg.attr('data-hcloud-series');
        if (! raw) {
            return null;
        }

        var parsed;
        try {
            parsed = JSON.parse(raw);
        } catch (e) {
            return null;
        }

        if (! parsed || ! Array.isArray(parsed.t) || ! Array.isArray(parsed.s)) {
            return null;
        }

        $svg.data('hcloudSeriesParsed', parsed);

        return parsed;
    }

    /**
     * Snap to the closest bucket that any series has a value for, so hovering a hole shows
     * the nearest real reading instead of a row of dashes.
     */
    function nearestWithValue(data, start) {
        var length = data.t.length;

        var has = function (index) {
            return data.s.some(function (series) {
                var value = series.v[index];
                return value !== null && typeof value !== 'undefined';
            });
        };

        if (has(start)) {
            return start;
        }

        for (var distance = 1; distance < length; distance++) {
            if (start - distance >= 0 && has(start - distance)) {
                return start - distance;
            }

            if (start + distance < length && has(start + distance)) {
                return start + distance;
            }
        }

        return null;
    }

    function readViewBoxWidth($svg) {
        var viewBox = ($svg.attr('viewBox') || '').split(/\s+/);

        return viewBox.length === 4 ? parseFloat(viewBox[2]) : 0;
    }

    function readPlot($svg) {
        var raw = ($svg.attr('data-hcloud-plot') || '').split(',');
        if (raw.length !== 4) {
            return null;
        }

        return {
            left: parseFloat(raw[0]),
            right: parseFloat(raw[1]),
            top: parseFloat(raw[2]),
            bottom: parseFloat(raw[3])
        };
    }

    Icinga.availableModules.hcloud = Hcloud;

})(Icinga, jQuery);
