import {mapState} from "vuex";

export default {
    computed: {
        ...mapState([
            'windowWidth',
            'windowHeight',

            'windowActive',
            'windowScrollY',

            'windowLarge',
            'windowSmall',

            'userId',
            'userToken',
        ])
    }
}
