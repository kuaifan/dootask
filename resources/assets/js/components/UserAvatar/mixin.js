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
        nameText: {
            type: String,
            default: null   // showName = true 时有效，留空就显示会员昵称
        },
        borderWitdh: {
            type: Number,
            default: 0
        },
        borderColor: {
            type: String,
            default: ''
        },
        clickOpenDialog: {
            type: Boolean,
            default: false
        },
        userResult: {
            default: null
        }
    }
}
