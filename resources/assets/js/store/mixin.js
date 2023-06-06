import {mapState} from "vuex";

export default {
    computed: {
        ...mapState([
            'windowWidth',
            'windowHeight',

            'windowActive',

            'windowScrollY',

            'windowTouch',

            'windowLandscape',
            'windowPortrait',

            'userId',
            'userToken',
        ])
    }
}
