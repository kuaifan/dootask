export default {
    name: 'ProjectLogDetail',
    functional: true,
    props: {
        render: Function,
        item: Object,
    },
    render: (h, ctx) => {
        return ctx.props.render(h, ctx.props.item);
    }
};
