<template>
    <div class="common-circle" :data-id="percent" :style="style">
        <svg viewBox="0 0 28 28">
            <g fill="none" fill-rule="evenodd">
                <path class="common-circle-path" d="M-500-100h997V48h-997z"></path>
                <g fill-rule="nonzero">
                    <path class="common-circle-g-path-ring" stroke-width="3" d="M14 25.5c6.351 0 11.5-5.149 11.5-11.5S20.351 2.5 14 2.5 2.5 7.649 2.5 14 7.649 25.5 14 25.5z"></path>
                    <path class="common-circle-g-path-core" :d="arc(args)"/>
                </g>
            </g>
        </svg>
    </div>
</template>

<script>
export default {
    name: 'WCircle',
    props: {
        percent: {
            type: Number,
            default: 0
        },
        size: {
            type: Number,
            default: 120
        },
    },

    computed: {
        style() {
            let {size} = this;
            if (this.isNumeric(size)) {
                size += 'px';
            }
            return {
                width: size,
                height: size,
            }
        },

        args() {
            const {percent} = this;
            let end = Math.min(360, 360 / 100 * percent);
            if (end == 360) {
                end = 0;
            } else if (end == 0) {
                end = 360;
            }
            return {
                x: 14,
                y: 14,
                r: 14,
                start: 360,
                end: end,
            }
        }
    },

    methods: {
        isNumeric(n) {
            return n !== '' && !isNaN(parseFloat(n)) && isFinite(n);
        },

        point(x, y, r, angel) {
            return [
                (x + Math.sin(angel) * r).toFixed(2),
                (y - Math.cos(angel) * r).toFixed(2),
            ]
        },

        full(x, y, R, r) {
            if (r <= 0) {
                return `M ${x - R} ${y} A ${R} ${R} 0 1 1 ${x + R} ${y} A ${R} ${R} 1 1 1 ${x - R} ${y} Z`;
            }
            return `M ${x - R} ${y} A ${R} ${R} 0 1 1 ${x + R} ${y} A ${R} ${R} 1 1 1 ${x - R} ${y} M ${x - r} ${y} A ${r} ${r} 0 1 1 ${x + r} ${y} A ${r} ${r} 1 1 1 ${x - r} ${y} Z`;
        },

        part(x, y, R, r, start, end) {
            const [s, e] = [(start / 360) * 2 * Math.PI, (end / 360) * 2 * Math.PI];
            const P = [
                this.point(x, y, r, s),
                this.point(x, y, R, s),
                this.point(x, y, R, e),
                this.point(x, y, r, e),
            ];
            const flag = e - s > Math.PI ? '1' : '0';
            return `M ${P[0][0]} ${P[0][1]} L ${P[1][0]} ${P[1][1]} A ${R} ${R} 0 ${flag} 1 ${P[2][0]} ${P[2][1]} L ${P[3][0]} ${P[3][1]} A ${r} ${r}  0 ${flag} 0 ${P[0][0]} ${P[0][1]} Z`;
        },

        arc(opts) {
            const { x = 0, y = 0 } = opts;
            let {R = 0, r = 0, start, end,} = opts;

            [R, r] = [Math.max(R, r), Math.min(R, r)];
            if (R <= 0) return '';
            if (start !== +start || end !== +end) return this.full(x, y, R, r);
            if (Math.abs(start - end) < 0.000001) return '';
            if (Math.abs(start - end) % 360 < 0.000001) return this.full(x, y, R, r);

            [start, end] = [start % 360, end % 360];

            if (start > end) end += 360;
            return this.part(x, y, R, r, start, end);
        }
    }
}
</script>
