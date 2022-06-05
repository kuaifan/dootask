import {mapState} from "vuex";

export default {
    computed: {
        ...mapState([
            'windowWidth',
            'windowHeight',
            'windowScrollY',

            'windowLarge',
            'windowSmall',

            'userId',
            'userToken',
        ])
    }
}
