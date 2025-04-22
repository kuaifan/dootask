export default {
    props: {
        userid: {
            type: [String, Number],
            default: ''
        },
        size: {
            type: [String, Number],
            default: 'default'
        },
        showIcon: {
            type: Boolean,
            default: true
        },
        showName: {
            type: Boolean,
            default: false
        },
        showStateDot: {
            type: Boolean,
            default: true
        },
        nameText: {
            type: String,
            default: ''   // showName = true 时有效，留空就显示会员昵称
        },
        borderWidth: {
            type: Number,
            default: 0
        },
        borderColor: {
            type: String,
            default: ''
        },
        clickOpenDetail: {
            type: Boolean,
            default: false
        },
        userResult: {
            default: null
        }
    }
}
