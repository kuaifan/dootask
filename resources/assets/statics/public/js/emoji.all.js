(function (wind) {
    let emojiData = [
        {
            "name": "100",
            "unicode": "1f4af",
            "shortname": ":100:",
            "code_decimal": "&#128175;",
            "category": "s",
            "emoji_order": "2119"
        },
        {
            "name": "1234",
            "unicode": "1f522",
            "shortname": ":1234:",
            "code_decimal": "&#128290;",
            "category": "s",
            "emoji_order": "2122"
        },
        {
            "name": "grinning",
            "unicode": "1f600",
            "shortname": ":grinning:",
            "code_decimal": "&#128512;",
            "category": "p",
            "emoji_order": "1"
        },
        {
            "name": "grin",
            "unicode": "1f601",
            "shortname": ":grin:",
            "code_decimal": "&#128513;",
            "category": "p",
            "emoji_order": "2"
        },
        {
            "name": "joy",
            "unicode": "1f602",
            "shortname": ":joy:",
            "code_decimal": "&#128514;",
            "category": "p",
            "emoji_order": "3"
        },
        /* ///@todo not found on image{
    "name": "rolling_on_the_floor_laughing",
    "unicode": "1f923",
    "shortname": ":rofl:",
    "code_decimal": "&#129315;",
    "category": "p",
    "emoji_order": "4"
  },*/
        {
            "name": "smiley",
            "unicode": "1f603",
            "shortname": ":smiley:",
            "code_decimal": "&#128515;",
            "category": "p",
            "emoji_order": "5"
        },
        {
            "name": "smile",
            "unicode": "1f604",
            "shortname": ":smile:",
            "code_decimal": "&#128516;",
            "category": "p",
            "emoji_order": "6"
        },
        {
            "name": "sweat_smile",
            "unicode": "1f605",
            "shortname": ":sweat_smile:",
            "code_decimal": "&#128517;",
            "category": "p",
            "emoji_order": "7"
        },
        {
            "name": "laughing",
            "unicode": "1f606",
            "shortname": ":laughing:",
            "code_decimal": "&#128518;",
            "category": "p",
            "emoji_order": "8"
        },
        {
            "name": "wink",
            "unicode": "1f609",
            "shortname": ":wink:",
            "code_decimal": "&#128521;",
            "category": "p",
            "emoji_order": "9"
        },
        {
            "name": "blush",
            "unicode": "1f60a",
            "shortname": ":blush:",
            "code_decimal": "&#128522;",
            "category": "p",
            "emoji_order": "10"
        },
        {
            "name": "yum",
            "unicode": "1f60b",
            "shortname": ":yum:",
            "code_decimal": "&#128523;",
            "category": "p",
            "emoji_order": "11"
        },
        {
            "name": "sunglasses",
            "unicode": "1f60e",
            "shortname": ":sunglasses:",
            "code_decimal": "&#128526;",
            "category": "p",
            "emoji_order": "12"
        },
        {
            "name": "heart_eyes",
            "unicode": "1f60d",
            "shortname": ":heart_eyes:",
            "code_decimal": "&#128525;",
            "category": "p",
            "emoji_order": "13"
        },
        {
            "name": "kissing_heart",
            "unicode": "1f618",
            "shortname": ":kissing_heart:",
            "code_decimal": "&#128536;",
            "category": "p",
            "emoji_order": "14"
        },
        {
            "name": "kissing",
            "unicode": "1f617",
            "shortname": ":kissing:",
            "code_decimal": "&#128535;",
            "category": "p",
            "emoji_order": "15"
        },
        {
            "name": "kissing_smiling_eyes",
            "unicode": "1f619",
            "shortname": ":kissing_smiling_eyes:",
            "code_decimal": "&#128537;",
            "category": "p",
            "emoji_order": "16"
        },
        {
            "name": "kissing_closed_eyes",
            "unicode": "1f61a",
            "shortname": ":kissing_closed_eyes:",
            "code_decimal": "&#128538;",
            "category": "p",
            "emoji_order": "17"
        },
        {
            "name": "slightly_smiling_face",
            "unicode": "1f642",
            "shortname": ":slight_smile:",
            "code_decimal": "&#128578;",
            "category": "p",
            "emoji_order": "19"
        },
        {
            "name": "hugging_face",
            "unicode": "1f917",
            "shortname": ":hugging:",
            "code_decimal": "&#129303;",
            "category": "p",
            "emoji_order": "20"
        },
        {
            "name": "thinking_face",
            "unicode": "1f914",
            "shortname": ":thinking:",
            "code_decimal": "&#129300;",
            "category": "p",
            "emoji_order": "21"
        },
        {
            "name": "neutral_face",
            "unicode": "1f610",
            "shortname": ":neutral_face:",
            "code_decimal": "&#128528;",
            "category": "p",
            "emoji_order": "22"
        },
        {
            "name": "expressionless",
            "unicode": "1f611",
            "shortname": ":expressionless:",
            "code_decimal": "&#128529;",
            "category": "p",
            "emoji_order": "23"
        },
        {
            "name": "no_mouth",
            "unicode": "1f636",
            "shortname": ":no_mouth:",
            "code_decimal": "&#128566;",
            "category": "p",
            "emoji_order": "24"
        },
        {
            "name": "face_with_rolling_eyes",
            "unicode": "1f644",
            "shortname": ":rolling_eyes:",
            "code_decimal": "&#128580;",
            "category": "p",
            "emoji_order": "25"
        },
        {
            "name": "smirk",
            "unicode": "1f60f",
            "shortname": ":smirk:",
            "code_decimal": "&#128527;",
            "category": "p",
            "emoji_order": "26"
        },
        {
            "name": "persevere",
            "unicode": "1f623",
            "shortname": ":persevere:",
            "code_decimal": "&#128547;",
            "category": "p",
            "emoji_order": "27"
        },
        {
            "name": "disappointed_relieved",
            "unicode": "1f625",
            "shortname": ":disappointed_relieved:",
            "code_decimal": "&#128549;",
            "category": "p",
            "emoji_order": "28"
        },
        {
            "name": "open_mouth",
            "unicode": "1f62e",
            "shortname": ":open_mouth:",
            "code_decimal": "&#128558;",
            "category": "p",
            "emoji_order": "29"
        },
        {
            "name": "zipper_mouth_face",
            "unicode": "1f910",
            "shortname": ":zipper_mouth:",
            "code_decimal": "&#129296;",
            "category": "p",
            "emoji_order": "30"
        },
        {
            "name": "hushed",
            "unicode": "1f62f",
            "shortname": ":hushed:",
            "code_decimal": "&#128559;",
            "category": "p",
            "emoji_order": "31"
        },
        {
            "name": "sleepy",
            "unicode": "1f62a",
            "shortname": ":sleepy:",
            "code_decimal": "&#128554;",
            "category": "p",
            "emoji_order": "32"
        },
        {
            "name": "tired_face",
            "unicode": "1f62b",
            "shortname": ":tired_face:",
            "code_decimal": "&#128555;",
            "category": "p",
            "emoji_order": "33"
        },
        {
            "name": "sleeping",
            "unicode": "1f634",
            "shortname": ":sleeping:",
            "code_decimal": "&#128564;",
            "category": "p",
            "emoji_order": "34"
        },
        {
            "name": "relieved",
            "unicode": "1f60c",
            "shortname": ":relieved:",
            "code_decimal": "&#128524;",
            "category": "p",
            "emoji_order": "35"
        },
        {
            "name": "nerd_face",
            "unicode": "1f913",
            "shortname": ":nerd:",
            "code_decimal": "&#129299;",
            "category": "p",
            "emoji_order": "36"
        },
        {
            "name": "stuck_out_tongue",
            "unicode": "1f61b",
            "shortname": ":stuck_out_tongue:",
            "code_decimal": "&#128539;",
            "category": "p",
            "emoji_order": "37"
        },
        {
            "name": "stuck_out_tongue_winking_eye",
            "unicode": "1f61c",
            "shortname": ":stuck_out_tongue_winking_eye:",
            "code_decimal": "&#128540;",
            "category": "p",
            "emoji_order": "38"
        },
        {
            "name": "stuck_out_tongue_closed_eyes",
            "unicode": "1f61d",
            "shortname": ":stuck_out_tongue_closed_eyes:",
            "code_decimal": "&#128541;",
            "category": "p",
            "emoji_order": "39"
        },
        /*{ //@todo not found on image
    "name": "drooling_face",
    "unicode": "1f924",
    "shortname": ":drooling_face:",
    "code_decimal": "&#129316;",
    "category": "p",
    "emoji_order": "40"
  },*/
        {
            "name": "unamused",
            "unicode": "1f612",
            "shortname": ":unamused:",
            "code_decimal": "&#128530;",
            "category": "p",
            "emoji_order": "41"
        },
        {
            "name": "sweat",
            "unicode": "1f613",
            "shortname": ":sweat:",
            "code_decimal": "&#128531;",
            "category": "p",
            "emoji_order": "42"
        },
        {
            "name": "pensive",
            "unicode": "1f614",
            "shortname": ":pensive:",
            "code_decimal": "&#128532;",
            "category": "p",
            "emoji_order": "43"
        },
        {
            "name": "confused",
            "unicode": "1f615",
            "shortname": ":confused:",
            "code_decimal": "&#128533;",
            "category": "p",
            "emoji_order": "44"
        },
        {
            "name": "upside_down_face",
            "unicode": "1f643",
            "shortname": ":upside_down:",
            "code_decimal": "&#128579;",
            "category": "p",
            "emoji_order": "45"
        },
        {
            "name": "money_mouth_face",
            "unicode": "1f911",
            "shortname": ":money_mouth:",
            "code_decimal": "&#129297;",
            "category": "p",
            "emoji_order": "46"
        },
        {
            "name": "astonished",
            "unicode": "1f632",
            "shortname": ":astonished:",
            "code_decimal": "&#128562;",
            "category": "p",
            "emoji_order": "47"
        },
        {
            "name": "slightly_frowning_face",
            "unicode": "1f641",
            "shortname": ":slight_frown:",
            "code_decimal": "&#128577;",
            "category": "p",
            "emoji_order": "49"
        },
        {
            "name": "confounded",
            "unicode": "1f616",
            "shortname": ":confounded:",
            "code_decimal": "&#128534;",
            "category": "p",
            "emoji_order": "50"
        },
        {
            "name": "disappointed",
            "unicode": "1f61e",
            "shortname": ":disappointed:",
            "code_decimal": "&#128542;",
            "category": "p",
            "emoji_order": "51"
        },
        {
            "name": "worried",
            "unicode": "1f61f",
            "shortname": ":worried:",
            "code_decimal": "&#128543;",
            "category": "p",
            "emoji_order": "52"
        },
        {
            "name": "triumph",
            "unicode": "1f624",
            "shortname": ":triumph:",
            "code_decimal": "&#128548;",
            "category": "p",
            "emoji_order": "53"
        },
        {
            "name": "cry",
            "unicode": "1f622",
            "shortname": ":cry:",
            "code_decimal": "&#128546;",
            "category": "p",
            "emoji_order": "54"
        },
        {
            "name": "sob",
            "unicode": "1f62d",
            "shortname": ":sob:",
            "code_decimal": "&#128557;",
            "category": "p",
            "emoji_order": "55"
        },
        {
            "name": "frowning",
            "unicode": "1f626",
            "shortname": ":frowning:",
            "code_decimal": "&#128550;",
            "category": "p",
            "emoji_order": "56"
        },
        {
            "name": "anguished",
            "unicode": "1f627",
            "shortname": ":anguished:",
            "code_decimal": "&#128551;",
            "category": "p",
            "emoji_order": "57"
        },
        {
            "name": "fearful",
            "unicode": "1f628",
            "shortname": ":fearful:",
            "code_decimal": "&#128552;",
            "category": "p",
            "emoji_order": "58"
        },
        {
            "name": "weary",
            "unicode": "1f629",
            "shortname": ":weary:",
            "code_decimal": "&#128553;",
            "category": "p",
            "emoji_order": "59"
        },
        {
            "name": "grimacing",
            "unicode": "1f62c",
            "shortname": ":grimacing:",
            "code_decimal": "&#128556;",
            "category": "p",
            "emoji_order": "60"
        },
        {
            "name": "cold_sweat",
            "unicode": "1f630",
            "shortname": ":cold_sweat:",
            "code_decimal": "&#128560;",
            "category": "p",
            "emoji_order": "61"
        },
        {
            "name": "scream",
            "unicode": "1f631",
            "shortname": ":scream:",
            "code_decimal": "&#128561;",
            "category": "p",
            "emoji_order": "62"
        },
        {
            "name": "flushed",
            "unicode": "1f633",
            "shortname": ":flushed:",
            "code_decimal": "&#128563;",
            "category": "p",
            "emoji_order": "63"
        },
        {
            "name": "dizzy_face",
            "unicode": "1f635",
            "shortname": ":dizzy_face:",
            "code_decimal": "&#128565;",
            "category": "p",
            "emoji_order": "64"
        },
        {
            "name": "rage",
            "unicode": "1f621",
            "shortname": ":rage:",
            "code_decimal": "&#128545;",
            "category": "p",
            "emoji_order": "65"
        },
        {
            "name": "angry",
            "unicode": "1f620",
            "shortname": ":angry:",
            "code_decimal": "&#128544;",
            "category": "p",
            "emoji_order": "66"
        },
        {
            "name": "innocent",
            "unicode": "1f607",
            "shortname": ":innocent:",
            "code_decimal": "&#128519;",
            "category": "p",
            "emoji_order": "67"
        },
        /*{ //@todo not found on image
    "name": "face_with_cowboy_hat",
    "unicode": "1f920",
    "shortname": ":cowboy:",
    "code_decimal": "&#129312;",
    "category": "p",
    "emoji_order": "68"
  },*/
        /*{ //@todo not found on image
    "name": "clown_face",
    "unicode": "1f921",
    "shortname": ":clown:",
    "code_decimal": "&#129313;",
    "category": "p",
    "emoji_order": "69"
  },*/
        /*{ //@todo not founf on image
    "name": "lying_face",
    "unicode": "1f925",
    "shortname": ":lying_face:",
    "code_decimal": "&#129317;",
    "category": "p",
    "emoji_order": "70"
  },*/
        {
            "name": "mask",
            "unicode": "1f637",
            "shortname": ":mask:",
            "code_decimal": "&#128567;",
            "category": "p",
            "emoji_order": "71"
        },
        {
            "name": "face_with_thermometer",
            "unicode": "1f912",
            "shortname": ":thermometer_face:",
            "code_decimal": "&#129298;",
            "category": "p",
            "emoji_order": "72"
        },
        {
            "name": "face_with_head_bandage",
            "unicode": "1f915",
            "shortname": ":head_bandage:",
            "code_decimal": "&#129301;",
            "category": "p",
            "emoji_order": "73"
        },
        /*{ //@todo not found on image
    "name": "nauseated_face",
    "unicode": "1f922",
    "shortname": ":nauseated_face:",
    "code_decimal": "&#129314;",
    "category": "p",
    "emoji_order": "74"
  },*/
        /*{ //@todo not found on image
    "name": "sneezing_face",
    "unicode": "1f927",
    "shortname": ":sneezing_face:",
    "code_decimal": "&#129319;",
    "category": "p",
    "emoji_order": "75"
  },*/
        {
            "name": "smiling_imp",
            "unicode": "1f608",
            "shortname": ":smiling_imp:",
            "code_decimal": "&#128520;",
            "category": "p",
            "emoji_order": "76"
        },
        {
            "name": "imp",
            "unicode": "1f47f",
            "shortname": ":imp:",
            "code_decimal": "&#128127;",
            "category": "p",
            "emoji_order": "77"
        },
        {
            "name": "japanese_ogre",
            "unicode": "1f479",
            "shortname": ":japanese_ogre:",
            "code_decimal": "&#128121;",
            "category": "p",
            "emoji_order": "78"
        },
        {
            "name": "japanese_goblin",
            "unicode": "1f47a",
            "shortname": ":japanese_goblin:",
            "code_decimal": "&#128122;",
            "category": "p",
            "emoji_order": "79"
        },
        {
            "name": "skull",
            "unicode": "1f480",
            "shortname": ":skull:",
            "code_decimal": "&#128128;",
            "category": "p",
            "emoji_order": "80"
        },
        {
            "name": "skull_and_crossbones",
            "unicode": "2620",
            "shortname": ":skull_crossbones:",
            "code_decimal": "&#9760;",
            "category": "o",
            "emoji_order": "81"
        },
        {
            "name": "ghost",
            "unicode": "1f47b",
            "shortname": ":ghost:",
            "code_decimal": "&#128123;",
            "category": "p",
            "emoji_order": "82"
        },
        {
            "name": "alien",
            "unicode": "1f47d",
            "shortname": ":alien:",
            "code_decimal": "&#128125;",
            "category": "p",
            "emoji_order": "83"
        },
        {
            "name": "space_invader",
            "unicode": "1f47e",
            "shortname": ":space_invader:",
            "code_decimal": "&#128126;",
            "category": "a",
            "emoji_order": "84"
        },
        {
            "name": "robot_face",
            "unicode": "1f916",
            "shortname": ":robot:",
            "code_decimal": "&#129302;",
            "category": "p",
            "emoji_order": "85"
        },
        {
            "name": "hankey",
            "unicode": "1f4a9",
            "shortname": ":poop:",
            "code_decimal": "&#128169;",
            "category": "p",
            "emoji_order": "86"
        },
        {
            "name": "smiley_cat",
            "unicode": "1f63a",
            "shortname": ":smiley_cat:",
            "code_decimal": "&#128570;",
            "category": "p",
            "emoji_order": "87"
        },
        {
            "name": "smile_cat",
            "unicode": "1f638",
            "shortname": ":smile_cat:",
            "code_decimal": "&#128568;",
            "category": "p",
            "emoji_order": "88"
        },
        {
            "name": "joy_cat",
            "unicode": "1f639",
            "shortname": ":joy_cat:",
            "code_decimal": "&#128569;",
            "category": "p",
            "emoji_order": "89"
        },
        {
            "name": "heart_eyes_cat",
            "unicode": "1f63b",
            "shortname": ":heart_eyes_cat:",
            "code_decimal": "&#128571;",
            "category": "p",
            "emoji_order": "90"
        },
        {
            "name": "smirk_cat",
            "unicode": "1f63c",
            "shortname": ":smirk_cat:",
            "code_decimal": "&#128572;",
            "category": "p",
            "emoji_order": "91"
        },
        {
            "name": "kissing_cat",
            "unicode": "1f63d",
            "shortname": ":kissing_cat:",
            "code_decimal": "&#128573;",
            "category": "p",
            "emoji_order": "92"
        },
        {
            "name": "scream_cat",
            "unicode": "1f640",
            "shortname": ":scream_cat:",
            "code_decimal": "&#128576;",
            "category": "p",
            "emoji_order": "93"
        },
        {
            "name": "crying_cat_face",
            "unicode": "1f63f",
            "shortname": ":crying_cat_face:",
            "code_decimal": "&#128575;",
            "category": "p",
            "emoji_order": "94"
        },
        {
            "name": "pouting_cat",
            "unicode": "1f63e",
            "shortname": ":pouting_cat:",
            "code_decimal": "&#128574;",
            "category": "p",
            "emoji_order": "95"
        },
        {
            "name": "see_no_evil",
            "unicode": "1f648",
            "shortname": ":see_no_evil:",
            "code_decimal": "&#128584;",
            "category": "n",
            "emoji_order": "96"
        },
        {
            "name": "hear_no_evil",
            "unicode": "1f649",
            "shortname": ":hear_no_evil:",
            "code_decimal": "&#128585;",
            "category": "n",
            "emoji_order": "97"
        },
        {
            "name": "speak_no_evil",
            "unicode": "1f64a",
            "shortname": ":speak_no_evil:",
            "code_decimal": "&#128586;",
            "category": "n",
            "emoji_order": "98"
        },
        {
            "name": "boy",
            "unicode": "1f466",
            "shortname": ":boy:",
            "code_decimal": "&#128102;",
            "category": "p",
            "emoji_order": "99"
        },
        {
            "name": "girl",
            "unicode": "1f467",
            "shortname": ":girl:",
            "code_decimal": "&#128103;",
            "category": "p",
            "emoji_order": "105"
        },
        {
            "name": "man",
            "unicode": "1f468",
            "shortname": ":man:",
            "code_decimal": "&#128104;",
            "category": "p",
            "emoji_order": "111"
        },
        {
            "name": "woman",
            "unicode": "1f469",
            "shortname": ":woman:",
            "code_decimal": "&#128105;",
            "category": "p",
            "emoji_order": "117"
        },
        {
            "name": "older_man",
            "unicode": "1f474",
            "shortname": ":older_man:",
            "code_decimal": "&#128116;",
            "category": "p",
            "emoji_order": "123"
        },
        {
            "name": "older_woman",
            "unicode": "1f475",
            "shortname": ":older_woman:",
            "code_decimal": "&#128117;",
            "category": "p",
            "emoji_order": "129"
        },
        {
            "name": "baby",
            "unicode": "1f476",
            "shortname": ":baby:",
            "code_decimal": "&#128118;",
            "category": "p",
            "emoji_order": "135"
        },
        {
            "name": "angel",
            "unicode": "1f47c",
            "shortname": ":angel:",
            "code_decimal": "&#128124;",
            "category": "p",
            "emoji_order": "141"
        },
        {
            "name": "cop",
            "unicode": "1f46e",
            "shortname": ":cop:",
            "code_decimal": "&#128110;",
            "category": "p",
            "emoji_order": "339"
        },
        {
            "name": "sleuth_or_spy",
            "unicode": "1f575",
            "shortname": ":spy:",
            "code_decimal": "&#128373;",
            "category": "p",
            "emoji_order": "357"
        },
        {
            "name": "guardsman",
            "unicode": "1f482",
            "shortname": ":guardsman:",
            "code_decimal": "&#128130;",
            "category": "p",
            "emoji_order": "375"
        },
        {
            "name": "construction_worker",
            "unicode": "1f477",
            "shortname": ":construction_worker:",
            "code_decimal": "&#128119;",
            "category": "p",
            "emoji_order": "393"
        },
        {
            "name": "man_with_turban",
            "unicode": "1f473",
            "shortname": ":man_with_turban:",
            "code_decimal": "&#128115;",
            "category": "p",
            "emoji_order": "411"
        },
        {
            "name": "person_with_blond_hair",
            "unicode": "1f471",
            "shortname": ":person_with_blond_hair:",
            "code_decimal": "&#128113;",
            "category": "p",
            "emoji_order": "429"
        },
        {
            "name": "santa",
            "unicode": "1f385",
            "shortname": ":santa:",
            "code_decimal": "&#127877;",
            "category": "p",
            "emoji_order": "447"
        },
        /*{ //@todo not found on image
    "name": "mrs_claus",
    "unicode": "1f936",
    "shortname": ":mrs_claus:",
    "code_decimal": "&#129334;",
    "category": "p",
    "emoji_order": "453"
  },*/
        {
            "name": "princess",
            "unicode": "1f478",
            "shortname": ":princess:",
            "code_decimal": "&#128120;",
            "category": "p",
            "emoji_order": "459"
        },
        /*{ //@todo not found on image
    "name": "prince",
    "unicode": "1f934",
    "shortname": ":prince:",
    "code_decimal": "&#129332;",
    "category": "p",
    "emoji_order": "465"
  },*/
        {
            "name": "bride_with_veil",
            "unicode": "1f470",
            "shortname": ":bride_with_veil:",
            "code_decimal": "&#128112;",
            "category": "p",
            "emoji_order": "471"
        },
        /*{ //@todo not found on image
    "name": "man_in_tuxedo",
    "unicode": "1f935",
    "shortname": ":man_in_tuxedo:",
    "code_decimal": "&#129333;",
    "category": "p",
    "emoji_order": "477"
  },*/
        /*{ //@todo not found on image
    "name": "pregnant_woman",
    "unicode": "1f930",
    "shortname": ":pregnant_woman:",
    "code_decimal": "&#129328;",
    "category": "p",
    "emoji_order": "483"
  },*/
        {
            "name": "man_with_gua_pi_mao",
            "unicode": "1f472",
            "shortname": ":man_with_gua_pi_mao:",
            "code_decimal": "&#128114;",
            "category": "p",
            "emoji_order": "489"
        },
        {
            "name": "person_frowning",
            "unicode": "1f64d",
            "shortname": ":person_frowning:",
            "code_decimal": "&#128589;",
            "category": "p",
            "emoji_order": "495"
        },
        {
            "name": "person_with_pouting_face",
            "unicode": "1f64e",
            "shortname": ":person_with_pouting_face:",
            "code_decimal": "&#128590;",
            "category": "p",
            "emoji_order": "513"
        },
        {
            "name": "no_good",
            "unicode": "1f645",
            "shortname": ":no_good:",
            "code_decimal": "&#128581;",
            "category": "p",
            "emoji_order": "531"
        },
        {
            "name": "ok_woman",
            "unicode": "1f646",
            "shortname": ":ok_woman:",
            "code_decimal": "&#128582;",
            "category": "p",
            "emoji_order": "549"
        },
        {
            "name": "information_desk_person",
            "unicode": "1f481",
            "shortname": ":information_desk_person:",
            "code_decimal": "&#128129;",
            "category": "p",
            "emoji_order": "567"
        },
        {
            "name": "raising_hand",
            "unicode": "1f64b",
            "shortname": ":raising_hand:",
            "code_decimal": "&#128587;",
            "category": "p",
            "emoji_order": "585"
        },
        {
            "name": "bow",
            "unicode": "1f647",
            "shortname": ":bow:",
            "code_decimal": "&#128583;",
            "category": "p",
            "emoji_order": "603"
        },
        /*{ //@todo not found on image
    "name": "face_palm",
    "unicode": "1f926",
    "shortname": ":face_palm:",
    "code_decimal": "&#129318;",
    "category": "p",
    "emoji_order": "621"
  },*/
        /*{ //@todo not found on image
    "name": "shrug",
    "unicode": "1f937",
    "shortname": ":shrug:",
    "code_decimal": "&#129335;",
    "category": "p",
    "emoji_order": "639"
  },*/
        {
            "name": "massage",
            "unicode": "1f486",
            "shortname": ":massage:",
            "code_decimal": "&#128134;",
            "category": "p",
            "emoji_order": "657"
        },
        {
            "name": "haircut",
            "unicode": "1f487",
            "shortname": ":haircut:",
            "code_decimal": "&#128135;",
            "category": "p",
            "emoji_order": "675"
        },
        {
            "name": "walking",
            "unicode": "1f6b6",
            "shortname": ":walking:",
            "code_decimal": "&#128694;",
            "category": "p",
            "emoji_order": "693"
        },
        {
            "name": "runner",
            "unicode": "1f3c3",
            "shortname": ":runner:",
            "code_decimal": "&#127939;",
            "category": "p",
            "emoji_order": "711"
        },
        {
            "name": "dancer",
            "unicode": "1f483",
            "shortname": ":dancer:",
            "code_decimal": "&#128131;",
            "category": "p",
            "emoji_order": "729"
        },
        /*{ //@todo not found on image
    "name": "man_dancing",
    "unicode": "1f57a",
    "shortname": ":man_dancing:",
    "code_decimal": "&#128378;",
    "category": "p",
    "emoji_order": "735"
  },*/
        {
            "name": "dancers",
            "unicode": "1f46f",
            "shortname": ":dancers:",
            "code_decimal": "&#128111;",
            "category": "p",
            "emoji_order": "741"
        },
        {
            "name": "man_in_business_suit_levitating",
            "unicode": "1f574",
            "shortname": ":levitate:",
            "code_decimal": "&#128372;",
            "category": "a",
            "emoji_order": "759"
        },
        {
            "name": "speaking_head_in_silhouette",
            "unicode": "1f5e3",
            "shortname": ":speaking_head:",
            "code_decimal": "&#128483;",
            "category": "p",
            "emoji_order": "765"
        },
        {
            "name": "bust_in_silhouette",
            "unicode": "1f464",
            "shortname": ":bust_in_silhouette:",
            "code_decimal": "&#128100;",
            "category": "p",
            "emoji_order": "766"
        },
        {
            "name": "busts_in_silhouette",
            "unicode": "1f465",
            "shortname": ":busts_in_silhouette:",
            "code_decimal": "&#128101;",
            "category": "p",
            "emoji_order": "767"
        },
        /*{ //@todo not found on image
    "name": "fencer",
    "unicode": "1f93a",
    "shortname": ":fencer:",
    "code_decimal": "&#129338;",
    "category": "a",
    "emoji_order": "768"
  },*/
        {
            "name": "horse_racing",
            "unicode": "1f3c7",
            "shortname": ":horse_racing:",
            "code_decimal": "&#127943;",
            "category": "a",
            "emoji_order": "769"
        },
        {
            "name": "skier",
            "unicode": "26f7",
            "shortname": ":skier:",
            "code_decimal": "&#9975;",
            "category": "a",
            "emoji_order": "775"
        },
        {
            "name": "snowboarder",
            "unicode": "1f3c2",
            "shortname": ":snowboarder:",
            "code_decimal": "&#127938;",
            "category": "a",
            "emoji_order": "776"
        },
        {
            "name": "golfer",
            "unicode": "1f3cc",
            "shortname": ":golfer:",
            "code_decimal": "&#127948;",
            "category": "a",
            "emoji_order": "782"
        },
        {
            "name": "surfer",
            "unicode": "1f3c4",
            "shortname": ":surfer:",
            "code_decimal": "&#127940;",
            "category": "a",
            "emoji_order": "800"
        },
        {
            "name": "rowboat",
            "unicode": "1f6a3",
            "shortname": ":rowboat:",
            "code_decimal": "&#128675;",
            "category": "a",
            "emoji_order": "818"
        },
        {
            "name": "swimmer",
            "unicode": "1f3ca",
            "shortname": ":swimmer:",
            "code_decimal": "&#127946;",
            "category": "a",
            "emoji_order": "836"
        },
        {
            "name": "person_with_ball",
            "unicode": "26f9",
            "shortname": ":basketball_player:",
            "code_decimal": "&#9977;",
            "category": "a",
            "emoji_order": "854"
        },
        {
            "name": "weight_lifter",
            "unicode": "1f3cb",
            "shortname": ":lifter:",
            "code_decimal": "&#127947;",
            "category": "a",
            "emoji_order": "872"
        },
        {
            "name": "bicyclist",
            "unicode": "1f6b4",
            "shortname": ":bicyclist:",
            "code_decimal": "&#128692;",
            "category": "a",
            "emoji_order": "890"
        },
        {
            "name": "mountain_bicyclist",
            "unicode": "1f6b5",
            "shortname": ":mountain_bicyclist:",
            "code_decimal": "&#128693;",
            "category": "a",
            "emoji_order": "908"
        },
        {
            "name": "racing_car",
            "unicode": "1f3ce",
            "shortname": ":race_car:",
            "code_decimal": "&#127950;",
            "category": "t",
            "emoji_order": "926"
        },
        {
            "name": "racing_motorcycle",
            "unicode": "1f3cd",
            "shortname": ":motorcycle:",
            "code_decimal": "&#127949;",
            "category": "t",
            "emoji_order": "927"
        },
        /*{ //@todo not found on image
    "name": "cartwheel",
    "unicode": "1f938",
    "shortname": ":cartwheel:",
    "code_decimal": "&#129336;",
    "category": "a",
    "emoji_order": "928"
  },*/
        /*{ //@todo not found on image
    "name": "wrestlers",
    "unicode": "1f93c",
    "shortname": ":wrestlers:",
    "code_decimal": "&#129340;",
    "category": "a",
    "emoji_order": "946"
  },*/
        /*{ //@todo not found on image
    "name": "water_polo",
    "unicode": "1f93d",
    "shortname": ":water_polo:",
    "code_decimal": "&#129341;",
    "category": "a",
    "emoji_order": "964"
  },*/
        /*{ //@todo not found on image
    "name": "handball",
    "unicode": "1f93e",
    "shortname": ":handball:",
    "code_decimal": "&#129342;",
    "category": "a",
    "emoji_order": "982"
  },*/
        /*{ //@todo not found on image
    "name": "juggling",
    "unicode": "1f939",
    "shortname": ":juggling:",
    "code_decimal": "&#129337;",
    "category": "a",
    "emoji_order": "1000"
  },*/
        {
            "name": "couple",
            "unicode": "1f46b",
            "shortname": ":couple:",
            "code_decimal": "&#128107;",
            "category": "p",
            "emoji_order": "1018"
        },
        {
            "name": "two_men_holding_hands",
            "unicode": "1f46c",
            "shortname": ":two_men_holding_hands:",
            "code_decimal": "&#128108;",
            "category": "p",
            "emoji_order": "1024"
        },
        {
            "name": "two_women_holding_hands",
            "unicode": "1f46d",
            "shortname": ":two_women_holding_hands:",
            "code_decimal": "&#128109;",
            "category": "p",
            "emoji_order": "1030"
        },
        {
            "name": "couplekiss",
            "unicode": "1f48f",
            "shortname": ":couplekiss:",
            "code_decimal": "&#128143;",
            "category": "p",
            "emoji_order": "1036"
        },

        {
            "name": "couple_with_heart",
            "unicode": "1f491",
            "shortname": ":couple_with_heart:",
            "code_decimal": "&#128145;",
            "category": "p",
            "emoji_order": "1040"
        },
        {
            "name": "family",
            "unicode": "1f46a",
            "shortname": ":family:",
            "code_decimal": "&#128106;",
            "category": "p",
            "emoji_order": "1044"
        },
        {
            "name": "muscle",
            "unicode": "1f4aa",
            "shortname": ":muscle:",
            "code_decimal": "&#128170;",
            "category": "p",
            "emoji_order": "1080"
        },
        /*{ //@todo not found on image
    "name": "selfie",
    "unicode": "1f933",
    "shortname": ":selfie:",
    "code_decimal": "&#129331;",
    "category": "p",
    "emoji_order": "1086"
  },*/
        {
            "name": "point_left",
            "unicode": "1f448",
            "shortname": ":point_left:",
            "code_decimal": "&#128072;",
            "category": "p",
            "emoji_order": "1092"
        },
        {
            "name": "point_right",
            "unicode": "1f449",
            "shortname": ":point_right:",
            "code_decimal": "&#128073;",
            "category": "p",
            "emoji_order": "1098"
        },
        {
            "name": "point_up",
            "unicode": "261d",
            "shortname": ":point_up:",
            "code_decimal": "&#9757;",
            "category": "p",
            "emoji_order": "1104"
        },
        {
            "name": "point_up_2",
            "unicode": "1f446",
            "shortname": ":point_up_2:",
            "code_decimal": "&#128070;",
            "category": "p",
            "emoji_order": "1110"
        },
        {
            "name": "middle_finger",
            "unicode": "1f595",
            "shortname": ":middle_finger:",
            "code_decimal": "&#128405;",
            "category": "p",
            "emoji_order": "1116"
        },
        {
            "name": "point_down",
            "unicode": "1f447",
            "shortname": ":point_down:",
            "code_decimal": "&#128071;",
            "category": "p",
            "emoji_order": "1122"
        },
        {
            "name": "v",
            "unicode": "270c",
            "shortname": ":v:",
            "code_decimal": "&#9996;",
            "category": "p",
            "emoji_order": "1128"
        },
        /*{ //@todo not found on image
    "name": "fingers_crossed",
    "unicode": "1f91e",
    "shortname": ":fingers_crossed:",
    "code_decimal": "&#129310;",
    "category": "p",
    "emoji_order": "1134"
  },*/
        /*{ //@todo not found on image
    "name": "vulcan",
    "unicode": "1f596",
    "shortname": ":vulcan:",
    "code_decimal": "&#128406;",
    "category": "p",
    "emoji_order": "1140"
  },*/
        {
            "name": "the_horns",
            "unicode": "1f918",
            "shortname": ":metal_tone2:",
            "code_decimal": "&#129304;",
            "category": "p",
            "emoji_order": "1146"
        },
        /*{ //@todo not found on image
    "name": "call_me",
    "unicode": "1f919",
    "shortname": ":call_me:",
    "code_decimal": "&#129305;",
    "category": "p",
    "emoji_order": "1152"
  },*/
        {
            "name": "raised_hand_with_fingers_splayed",
            "unicode": "1f590",
            "shortname": ":hand_splayed:",
            "code_decimal": "&#128400;",
            "category": "p",
            "emoji_order": "1158"
        },
        /*{ //@todo not found on image
    "name": "raised_hand",
    "unicode": "270b",
    "shortname": ":raised_hand:",
    "code_decimal": "&#9995;",
    "category": "p",
    "emoji_order": "1164"
  },*/
        {
            "name": "ok_hand",
            "unicode": "1f44c",
            "shortname": ":ok_hand:",
            "code_decimal": "&#128076;",
            "category": "p",
            "emoji_order": "1170"
        },
        {
            "name": "thumbsup",
            "unicode": "1f44d",
            "shortname": ":thumbsup:",
            "code_decimal": "&#128077;",
            "category": "p",
            "emoji_order": "1176"
        },
        {
            "name": "thumbsdown",
            "unicode": "1f44e",
            "shortname": ":thumbsdown:",
            "code_decimal": "&#128078;",
            "category": "p",
            "emoji_order": "1182"
        },
        {
            "name": "fist",
            "unicode": "270a",
            "shortname": ":fist:",
            "code_decimal": "&#9994;",
            "category": "p",
            "emoji_order": "1188"
        },
        {
            "name": "facepunch",
            "unicode": "1f44a",
            "shortname": ":punch:",
            "code_decimal": "&#128074;",
            "category": "p",
            "emoji_order": "1194"
        },
        /*{ //@todo not found on image
    "name": "left_facing_fist",
    "unicode": "1f91b",
    "shortname": ":left_facing_fist:",
    "code_decimal": "&#129307;",
    "category": "p",
    "emoji_order": "1200"
  },*/
        /*{ //@todo not found on image
    "name": "right_facing_fist",
    "unicode": "1f91c",
    "shortname": ":right_facing_fist:",
    "code_decimal": "&#129308;",
    "category": "p",
    "emoji_order": "1206"
  },*/
        /*{ //@todo not found on image
    "name": "raised_back_of_hand",
    "unicode": "1f91a",
    "shortname": ":raised_back_of_hand:",
    "code_decimal": "&#129306;",
    "category": "p",
    "emoji_order": "1212"
  },*/
        {
            "name": "wave",
            "unicode": "1f44b",
            "shortname": ":wave:",
            "code_decimal": "&#128075;",
            "category": "p",
            "emoji_order": "1218"
        },
        {
            "name": "clap",
            "unicode": "1f44f",
            "shortname": ":clap:",
            "code_decimal": "&#128079;",
            "category": "p",
            "emoji_order": "1224"
        },
        {
            "name": "writing_hand",
            "unicode": "270d",
            "shortname": ":writing_hand:",
            "code_decimal": "&#9997;",
            "category": "p",
            "emoji_order": "1230"
        },
        {
            "name": "open_hands",
            "unicode": "1f450",
            "shortname": ":open_hands:",
            "code_decimal": "&#128080;",
            "category": "p",
            "emoji_order": "1236"
        },
        {
            "name": "raised_hands",
            "unicode": "1f64c",
            "shortname": ":raised_hands:",
            "code_decimal": "&#128588;",
            "category": "p",
            "emoji_order": "1242"
        },
        {
            "name": "pray",
            "unicode": "1f64f",
            "shortname": ":pray:",
            "code_decimal": "&#128591;",
            "category": "p",
            "emoji_order": "1248"
        },
        /*{ //@todo not found on image
    "name": "handshake",
    "unicode": "1f91d",
    "shortname": ":handshake:",
    "code_decimal": "&#129309;",
    "category": "p",
    "emoji_order": "1254"
  },*/
        {
            "name": "nail_care",
            "unicode": "1f485",
            "shortname": ":nail_care:",
            "code_decimal": "&#128133;",
            "category": "p",
            "emoji_order": "1260"
        },
        {
            "name": "ear",
            "unicode": "1f442",
            "shortname": ":ear:",
            "code_decimal": "&#128066;",
            "category": "p",
            "emoji_order": "1266"
        },
        {
            "name": "nose",
            "unicode": "1f443",
            "shortname": ":nose:",
            "code_decimal": "&#128067;",
            "category": "p",
            "emoji_order": "1272"
        },
        {
            "name": "footprints",
            "unicode": "1f463",
            "shortname": ":footprints:",
            "code_decimal": "&#128099;",
            "category": "p",
            "emoji_order": "1278"
        },
        {
            "name": "eyes",
            "unicode": "1f440",
            "shortname": ":eyes:",
            "code_decimal": "&#128064;",
            "category": "p",
            "emoji_order": "1279"
        },
        {
            "name": "eye",
            "unicode": "1f441",
            "shortname": ":eye:",
            "code_decimal": "&#128065;",
            "category": "p",
            "emoji_order": "1280"
        },
        {
            "name": "tongue",
            "unicode": "1f445",
            "shortname": ":tongue:",
            "code_decimal": "&#128069;",
            "category": "p",
            "emoji_order": "1282"
        },
        {
            "name": "lips",
            "unicode": "1f444",
            "shortname": ":lips:",
            "code_decimal": "&#128068;",
            "category": "p",
            "emoji_order": "1283"
        },
        {
            "name": "kiss",
            "unicode": "1f48b",
            "shortname": ":kiss:",
            "code_decimal": "&#128139;",
            "category": "p",
            "emoji_order": "1284"
        },
        {
            "name": "cupid",
            "unicode": "1f498",
            "shortname": ":cupid:",
            "code_decimal": "&#128152;",
            "category": "s",
            "emoji_order": "1285"
        },
        {
            "name": "heart",
            "unicode": "2764",
            "shortname": ":heart:",
            "code_decimal": "&#10084;",
            "category": "s",
            "emoji_order": "1286"
        },
        {
            "name": "heartbeat",
            "unicode": "1f493",
            "shortname": ":heartbeat:",
            "code_decimal": "&#128147;",
            "category": "s",
            "emoji_order": "1287"
        },
        {
            "name": "broken_heart",
            "unicode": "1f494",
            "shortname": ":broken_heart:",
            "code_decimal": "&#128148;",
            "category": "s",
            "emoji_order": "1288"
        },
        {
            "name": "two_hearts",
            "unicode": "1f495",
            "shortname": ":two_hearts:",
            "code_decimal": "&#128149;",
            "category": "s",
            "emoji_order": "1289"
        },
        {
            "name": "sparkling_heart",
            "unicode": "1f496",
            "shortname": ":sparkling_heart:",
            "code_decimal": "&#128150;",
            "category": "s",
            "emoji_order": "1290"
        },
        {
            "name": "heartpulse",
            "unicode": "1f497",
            "shortname": ":heartpulse:",
            "code_decimal": "&#128151;",
            "category": "s",
            "emoji_order": "1291"
        },
        {
            "name": "blue_heart",
            "unicode": "1f499",
            "shortname": ":blue_heart:",
            "code_decimal": "&#128153;",
            "category": "s",
            "emoji_order": "1292"
        },
        {
            "name": "green_heart",
            "unicode": "1f49a",
            "shortname": ":green_heart:",
            "code_decimal": "&#128154;",
            "category": "s",
            "emoji_order": "1293"
        },
        {
            "name": "yellow_heart",
            "unicode": "1f49b",
            "shortname": ":yellow_heart:",
            "code_decimal": "&#128155;",
            "category": "s",
            "emoji_order": "1294"
        },
        {
            "name": "purple_heart",
            "unicode": "1f49c",
            "shortname": ":purple_heart:",
            "code_decimal": "&#128156;",
            "category": "s",
            "emoji_order": "1295"
        },
        /*{ //@todo not found on image
    "name": "black_heart",
    "unicode": "1f5a4",
    "shortname": ":black_heart:",
    "code_decimal": "&#128420;",
    "category": "s",
    "emoji_order": "1296"
  },*/
        {
            "name": "gift_heart",
            "unicode": "1f49d",
            "shortname": ":gift_heart:",
            "code_decimal": "&#128157;",
            "category": "s",
            "emoji_order": "1297"
        },
        {
            "name": "revolving_hearts",
            "unicode": "1f49e",
            "shortname": ":revolving_hearts:",
            "code_decimal": "&#128158;",
            "category": "s",
            "emoji_order": "1298"
        },
        {
            "name": "heart_decoration",
            "unicode": "1f49f",
            "shortname": ":heart_decoration:",
            "code_decimal": "&#128159;",
            "category": "s",
            "emoji_order": "1299"
        },
        {
            "name": "heart_exclamation",
            "unicode": "2763",
            "shortname": ":heart_exclamation:",
            "code_decimal": "&#10083;",
            "category": "s",
            "emoji_order": "1300"
        },
        {
            "name": "love_letter",
            "unicode": "1f48c",
            "shortname": ":love_letter:",
            "code_decimal": "&#128140;",
            "category": "o",
            "emoji_order": "1301"
        },
        {
            "name": "zzz",
            "unicode": "1f4a4",
            "shortname": ":zzz:",
            "code_decimal": "&#128164;",
            "category": "p",
            "emoji_order": "1302"
        },
        {
            "name": "anger",
            "unicode": "1f4a2",
            "shortname": ":anger:",
            "code_decimal": "&#128162;",
            "category": "s",
            "emoji_order": "1303"
        },
        {
            "name": "bomb",
            "unicode": "1f4a3",
            "shortname": ":bomb:",
            "code_decimal": "&#128163;",
            "category": "o",
            "emoji_order": "1304"
        },
        {
            "name": "boom",
            "unicode": "1f4a5",
            "shortname": ":boom:",
            "code_decimal": "&#128165;",
            "category": "s",
            "emoji_order": "1305"
        },
        {
            "name": "sweat_drops",
            "unicode": "1f4a6",
            "shortname": ":sweat_drops:",
            "code_decimal": "&#128166;",
            "category": "n",
            "emoji_order": "1306"
        },
        {
            "name": "dash",
            "unicode": "1f4a8",
            "shortname": ":dash:",
            "code_decimal": "&#128168;",
            "category": "n",
            "emoji_order": "1307"
        },
        {
            "name": "dizzy",
            "unicode": "1f4ab",
            "shortname": ":dizzy:",
            "code_decimal": "&#128171;",
            "category": "s",
            "emoji_order": "1308"
        },
        {
            "name": "speech_balloon",
            "unicode": "1f4ac",
            "shortname": ":speech_balloon:",
            "code_decimal": "&#128172;",
            "category": "s",
            "emoji_order": "1309"
        },
        {
            "name": "left_speech_bubble",
            "unicode": "1f5e8",
            "shortname": ":speech_left:",
            "code_decimal": "&#128488;",
            "category": "s",
            "emoji_order": "1310"
        },
        {
            "name": "right_anger_bubble",
            "unicode": "1f5ef",
            "shortname": ":anger_right:",
            "code_decimal": "&#128495;",
            "category": "s",
            "emoji_order": "1311"
        },
        {
            "name": "thought_balloon",
            "unicode": "1f4ad",
            "shortname": ":thought_balloon:",
            "code_decimal": "&#128173;",
            "category": "s",
            "emoji_order": "1312"
        },
        {
            "name": "hole",
            "unicode": "1f573",
            "shortname": ":hole:",
            "code_decimal": "&#128371;",
            "category": "o",
            "emoji_order": "1313"
        },
        {
            "name": "eyeglasses",
            "unicode": "1f453",
            "shortname": ":eyeglasses:",
            "code_decimal": "&#128083;",
            "category": "p",
            "emoji_order": "1314"
        },
        {
            "name": "dark_sunglasses",
            "unicode": "1f576",
            "shortname": ":dark_sunglasses:",
            "code_decimal": "&#128374;",
            "category": "p",
            "emoji_order": "1315"
        },
        {
            "name": "necktie",
            "unicode": "1f454",
            "shortname": ":necktie:",
            "code_decimal": "&#128084;",
            "category": "p",
            "emoji_order": "1316"
        },
        {
            "name": "shirt",
            "unicode": "1f455",
            "shortname": ":shirt:",
            "code_decimal": "&#128085;",
            "category": "p",
            "emoji_order": "1317"
        },
        {
            "name": "jeans",
            "unicode": "1f456",
            "shortname": ":jeans:",
            "code_decimal": "&#128086;",
            "category": "p",
            "emoji_order": "1318"
        },
        {
            "name": "dress",
            "unicode": "1f457",
            "shortname": ":dress:",
            "code_decimal": "&#128087;",
            "category": "p",
            "emoji_order": "1319"
        },
        {
            "name": "kimono",
            "unicode": "1f458",
            "shortname": ":kimono:",
            "code_decimal": "&#128088;",
            "category": "p",
            "emoji_order": "1320"
        },
        {
            "name": "bikini",
            "unicode": "1f459",
            "shortname": ":bikini:",
            "code_decimal": "&#128089;",
            "category": "p",
            "emoji_order": "1321"
        },
        {
            "name": "womans_clothes",
            "unicode": "1f45a",
            "shortname": ":womans_clothes:",
            "code_decimal": "&#128090;",
            "category": "p",
            "emoji_order": "1322"
        },
        {
            "name": "purse",
            "unicode": "1f45b",
            "shortname": ":purse:",
            "code_decimal": "&#128091;",
            "category": "p",
            "emoji_order": "1323"
        },
        {
            "name": "handbag",
            "unicode": "1f45c",
            "shortname": ":handbag:",
            "code_decimal": "&#128092;",
            "category": "p",
            "emoji_order": "1324"
        },
        {
            "name": "pouch",
            "unicode": "1f45d",
            "shortname": ":pouch:",
            "code_decimal": "&#128093;",
            "category": "p",
            "emoji_order": "1325"
        },
        {
            "name": "shopping_bags",
            "unicode": "1f6cd",
            "shortname": ":shopping_bags:",
            "code_decimal": "&#128717;",
            "category": "o",
            "emoji_order": "1326"
        },
        {
            "name": "school_satchel",
            "unicode": "1f392",
            "shortname": ":school_satchel:",
            "code_decimal": "&#127890;",
            "category": "p",
            "emoji_order": "1327"
        },
        {
            "name": "mans_shoe",
            "unicode": "1f45e",
            "shortname": ":mans_shoe:",
            "code_decimal": "&#128094;",
            "category": "p",
            "emoji_order": "1328"
        },
        {
            "name": "athletic_shoe",
            "unicode": "1f45f",
            "shortname": ":athletic_shoe:",
            "code_decimal": "&#128095;",
            "category": "p",
            "emoji_order": "1329"
        },
        {
            "name": "high_heel",
            "unicode": "1f460",
            "shortname": ":high_heel:",
            "code_decimal": "&#128096;",
            "category": "p",
            "emoji_order": "1330"
        },
        {
            "name": "sandal",
            "unicode": "1f461",
            "shortname": ":sandal:",
            "code_decimal": "&#128097;",
            "category": "p",
            "emoji_order": "1331"
        },
        {
            "name": "boot",
            "unicode": "1f462",
            "shortname": ":boot:",
            "code_decimal": "&#128098;",
            "category": "p",
            "emoji_order": "1332"
        },
        {
            "name": "crown",
            "unicode": "1f451",
            "shortname": ":crown:",
            "code_decimal": "&#128081;",
            "category": "p",
            "emoji_order": "1333"
        },
        {
            "name": "womans_hat",
            "unicode": "1f452",
            "shortname": ":womans_hat:",
            "code_decimal": "&#128082;",
            "category": "p",
            "emoji_order": "1334"
        },
        {
            "name": "tophat",
            "unicode": "1f3a9",
            "shortname": ":tophat:",
            "code_decimal": "&#127913;",
            "category": "p",
            "emoji_order": "1335"
        },
        {
            "name": "mortar_board",
            "unicode": "1f393",
            "shortname": ":mortar_board:",
            "code_decimal": "&#127891;",
            "category": "p",
            "emoji_order": "1336"
        },
        {
            "name": "helmet_with_white_cross",
            "unicode": "26d1",
            "shortname": ":helmet_with_cross:",
            "code_decimal": "&#9937;",
            "category": "p",
            "emoji_order": "1337"
        },
        {
            "name": "prayer_beads",
            "unicode": "1f4ff",
            "shortname": ":prayer_beads:",
            "code_decimal": "&#128255;",
            "category": "o",
            "emoji_order": "1338"
        },
        {
            "name": "lipstick",
            "unicode": "1f484",
            "shortname": ":lipstick:",
            "code_decimal": "&#128132;",
            "category": "p",
            "emoji_order": "1339"
        },
        {
            "name": "ring",
            "unicode": "1f48d",
            "shortname": ":ring:",
            "code_decimal": "&#128141;",
            "category": "p",
            "emoji_order": "1340"
        },
        {
            "name": "gem",
            "unicode": "1f48e",
            "shortname": ":gem:",
            "code_decimal": "&#128142;",
            "category": "o",
            "emoji_order": "1341"
        },
        {
            "name": "monkey_face",
            "unicode": "1f435",
            "shortname": ":monkey_face:",
            "code_decimal": "&#128053;",
            "category": "n",
            "emoji_order": "1342"
        },
        {
            "name": "monkey",
            "unicode": "1f412",
            "shortname": ":monkey:",
            "code_decimal": "&#128018;",
            "category": "n",
            "emoji_order": "1343"
        },
        /*{ //@todo not found on image
    "name": "gorilla",
    "unicode": "1f98d",
    "shortname": ":gorilla:",
    "code_decimal": "&#129421;",
    "category": "n",
    "emoji_order": "1344"
  },*/
        {
            "name": "dog",
            "unicode": "1f436",
            "shortname": ":dog:",
            "code_decimal": "&#128054;",
            "category": "n",
            "emoji_order": "1345"
        },
        {
            "name": "dog2",
            "unicode": "1f415",
            "shortname": ":dog2:",
            "code_decimal": "&#128021;",
            "category": "n",
            "emoji_order": "1346"
        },
        {
            "name": "poodle",
            "unicode": "1f429",
            "shortname": ":poodle:",
            "code_decimal": "&#128041;",
            "category": "n",
            "emoji_order": "1347"
        },
        {
            "name": "wolf",
            "unicode": "1f43a",
            "shortname": ":wolf:",
            "code_decimal": "&#128058;",
            "category": "n",
            "emoji_order": "1348"
        },
        /*{ //@todo not found on image
    "name": "fox",
    "unicode": "1f98a",
    "shortname": ":fox:",
    "code_decimal": "&#129418;",
    "category": "n",
    "emoji_order": "1349"
  },*/
        {
            "name": "cat",
            "unicode": "1f431",
            "shortname": ":cat:",
            "code_decimal": "&#128049;",
            "category": "n",
            "emoji_order": "1350"
        },
        {
            "name": "cat2",
            "unicode": "1f408",
            "shortname": ":cat2:",
            "code_decimal": "&#128008;",
            "category": "n",
            "emoji_order": "1351"
        },
        {
            "name": "lion_face",
            "unicode": "1f981",
            "shortname": ":lion_face:",
            "code_decimal": "&#129409;",
            "category": "n",
            "emoji_order": "1352"
        },
        {
            "name": "tiger",
            "unicode": "1f42f",
            "shortname": ":tiger:",
            "code_decimal": "&#128047;",
            "category": "n",
            "emoji_order": "1353"
        },
        {
            "name": "tiger2",
            "unicode": "1f405",
            "shortname": ":tiger2:",
            "code_decimal": "&#128005;",
            "category": "n",
            "emoji_order": "1354"
        },
        {
            "name": "leopard",
            "unicode": "1f406",
            "shortname": ":leopard:",
            "code_decimal": "&#128006;",
            "category": "n",
            "emoji_order": "1355"
        },
        {
            "name": "horse",
            "unicode": "1f434",
            "shortname": ":horse:",
            "code_decimal": "&#128052;",
            "category": "n",
            "emoji_order": "1356"
        },
        {
            "name": "racehorse",
            "unicode": "1f40e",
            "shortname": ":racehorse:",
            "code_decimal": "&#128014;",
            "category": "n",
            "emoji_order": "1357"
        },
        /*{ //@todo not found on image
    "name": "deer",
    "unicode": "1f98c",
    "shortname": ":deer:",
    "code_decimal": "&#129420;",
    "category": "n",
    "emoji_order": "1358"
  },*/
        {
            "name": "unicorn_face",
            "unicode": "1f984",
            "shortname": ":unicorn:",
            "code_decimal": "&#129412;",
            "category": "n",
            "emoji_order": "1359"
        },
        {
            "name": "cow",
            "unicode": "1f42e",
            "shortname": ":cow:",
            "code_decimal": "&#128046;",
            "category": "n",
            "emoji_order": "1360"
        },
        {
            "name": "ox",
            "unicode": "1f402",
            "shortname": ":ox:",
            "code_decimal": "&#128002;",
            "category": "n",
            "emoji_order": "1361"
        },
        {
            "name": "water_buffalo",
            "unicode": "1f403",
            "shortname": ":water_buffalo:",
            "code_decimal": "&#128003;",
            "category": "n",
            "emoji_order": "1362"
        },
        {
            "name": "cow2",
            "unicode": "1f404",
            "shortname": ":cow2:",
            "code_decimal": "&#128004;",
            "category": "n",
            "emoji_order": "1363"
        },
        {
            "name": "pig",
            "unicode": "1f437",
            "shortname": ":pig:",
            "code_decimal": "&#128055;",
            "category": "n",
            "emoji_order": "1364"
        },
        {
            "name": "pig2",
            "unicode": "1f416",
            "shortname": ":pig2:",
            "code_decimal": "&#128022;",
            "category": "n",
            "emoji_order": "1365"
        },
        {
            "name": "boar",
            "unicode": "1f417",
            "shortname": ":boar:",
            "code_decimal": "&#128023;",
            "category": "n",
            "emoji_order": "1366"
        },
        {
            "name": "pig_nose",
            "unicode": "1f43d",
            "shortname": ":pig_nose:",
            "code_decimal": "&#128061;",
            "category": "n",
            "emoji_order": "1367"
        },
        {
            "name": "ram",
            "unicode": "1f40f",
            "shortname": ":ram:",
            "code_decimal": "&#128015;",
            "category": "n",
            "emoji_order": "1368"
        },
        {
            "name": "sheep",
            "unicode": "1f411",
            "shortname": ":sheep:",
            "code_decimal": "&#128017;",
            "category": "n",
            "emoji_order": "1369"
        },
        {
            "name": "goat",
            "unicode": "1f410",
            "shortname": ":goat:",
            "code_decimal": "&#128016;",
            "category": "n",
            "emoji_order": "1370"
        },
        {
            "name": "dromedary_camel",
            "unicode": "1f42a",
            "shortname": ":dromedary_camel:",
            "code_decimal": "&#128042;",
            "category": "n",
            "emoji_order": "1371"
        },
        {
            "name": "camel",
            "unicode": "1f42b",
            "shortname": ":camel:",
            "code_decimal": "&#128043;",
            "category": "n",
            "emoji_order": "1372"
        },
        {
            "name": "elephant",
            "unicode": "1f418",
            "shortname": ":elephant:",
            "code_decimal": "&#128024;",
            "category": "n",
            "emoji_order": "1373"
        },
        /*{ //@todo not found on image
    "name": "rhino",
    "unicode": "1f98f",
    "shortname": ":rhino:",
    "code_decimal": "&#129423;",
    "category": "n",
    "emoji_order": "1374"
  },*/
        {
            "name": "mouse",
            "unicode": "1f42d",
            "shortname": ":mouse:",
            "code_decimal": "&#128045;",
            "category": "n",
            "emoji_order": "1375"
        },
        {
            "name": "mouse2",
            "unicode": "1f401",
            "shortname": ":mouse2:",
            "code_decimal": "&#128001;",
            "category": "n",
            "emoji_order": "1376"
        },
        {
            "name": "rat",
            "unicode": "1f400",
            "shortname": ":rat:",
            "code_decimal": "&#128000;",
            "category": "n",
            "emoji_order": "1377"
        },
        {
            "name": "hamster",
            "unicode": "1f439",
            "shortname": ":hamster:",
            "code_decimal": "&#128057;",
            "category": "n",
            "emoji_order": "1378"
        },
        {
            "name": "rabbit",
            "unicode": "1f430",
            "shortname": ":rabbit:",
            "code_decimal": "&#128048;",
            "category": "n",
            "emoji_order": "1379"
        },
        {
            "name": "rabbit2",
            "unicode": "1f407",
            "shortname": ":rabbit2:",
            "code_decimal": "&#128007;",
            "category": "n",
            "emoji_order": "1380"
        },
        {
            "name": "chipmunk",
            "unicode": "1f43f",
            "shortname": ":chipmunk:",
            "code_decimal": "&#128063;",
            "category": "n",
            "emoji_order": "1381"
        },
        /*{ //@todo not found on image
    "name": "bat",
    "unicode": "1f987",
    "shortname": ":bat:",
    "code_decimal": "&#129415;",
    "category": "n",
    "emoji_order": "1382"
  },*/
        {
            "name": "bear",
            "unicode": "1f43b",
            "shortname": ":bear:",
            "code_decimal": "&#128059;",
            "category": "n",
            "emoji_order": "1383"
        },
        {
            "name": "koala",
            "unicode": "1f428",
            "shortname": ":koala:",
            "code_decimal": "&#128040;",
            "category": "n",
            "emoji_order": "1384"
        },
        {
            "name": "panda_face",
            "unicode": "1f43c",
            "shortname": ":panda_face:",
            "code_decimal": "&#128060;",
            "category": "n",
            "emoji_order": "1385"
        },
        {
            "name": "feet",
            "unicode": "1f43e",
            "shortname": ":feet:",
            "code_decimal": "&#128062;",
            "category": "n",
            "emoji_order": "1386"
        },
        {
            "name": "turkey",
            "unicode": "1f983",
            "shortname": ":turkey:",
            "code_decimal": "&#129411;",
            "category": "n",
            "emoji_order": "1387"
        },
        {
            "name": "chicken",
            "unicode": "1f414",
            "shortname": ":chicken:",
            "code_decimal": "&#128020;",
            "category": "n",
            "emoji_order": "1388"
        },
        {
            "name": "rooster",
            "unicode": "1f413",
            "shortname": ":rooster:",
            "code_decimal": "&#128019;",
            "category": "n",
            "emoji_order": "1389"
        },
        {
            "name": "hatching_chick",
            "unicode": "1f423",
            "shortname": ":hatching_chick:",
            "code_decimal": "&#128035;",
            "category": "n",
            "emoji_order": "1390"
        },
        {
            "name": "baby_chick",
            "unicode": "1f424",
            "shortname": ":baby_chick:",
            "code_decimal": "&#128036;",
            "category": "n",
            "emoji_order": "1391"
        },
        {
            "name": "hatched_chick",
            "unicode": "1f425",
            "shortname": ":hatched_chick:",
            "code_decimal": "&#128037;",
            "category": "n",
            "emoji_order": "1392"
        },
        {
            "name": "bird",
            "unicode": "1f426",
            "shortname": ":bird:",
            "code_decimal": "&#128038;",
            "category": "n",
            "emoji_order": "1393"
        },
        {
            "name": "penguin",
            "unicode": "1f427",
            "shortname": ":penguin:",
            "code_decimal": "&#128039;",
            "category": "n",
            "emoji_order": "1394"
        },
        {
            "name": "dove_of_peace",
            "unicode": "1f54a",
            "shortname": ":dove:",
            "code_decimal": "&#128330;",
            "category": "n",
            "emoji_order": "1395"
        },
        /*{ //@todo not found on image
    "name": "eagle",
    "unicode": "1f985",
    "shortname": ":eagle:",
    "code_decimal": "&#129413;",
    "category": "n",
    "emoji_order": "1396"
  },*/
        /*{ //@todo not found on image
    "name": "duck",
    "unicode": "1f986",
    "shortname": ":duck:",
    "code_decimal": "&#129414;",
    "category": "n",
    "emoji_order": "1397"
  },*/
        /*{ //@todo not found on image
    "name": "owl",
    "unicode": "1f989",
    "shortname": ":owl:",
    "code_decimal": "&#129417;",
    "category": "n",
    "emoji_order": "1398"
  },*/
        {
            "name": "frog",
            "unicode": "1f438",
            "shortname": ":frog:",
            "code_decimal": "&#128056;",
            "category": "n",
            "emoji_order": "1399"
        },
        {
            "name": "crocodile",
            "unicode": "1f40a",
            "shortname": ":crocodile:",
            "code_decimal": "&#128010;",
            "category": "n",
            "emoji_order": "1400"
        },
        {
            "name": "turtle",
            "unicode": "1f422",
            "shortname": ":turtle:",
            "code_decimal": "&#128034;",
            "category": "n",
            "emoji_order": "1401"
        },
        /*{ //@todo not found on image
    "name": "lizard",
    "unicode": "1f98e",
    "shortname": ":lizard:",
    "code_decimal": "&#129422;",
    "category": "n",
    "emoji_order": "1402"
  },*/
        {
            "name": "snake",
            "unicode": "1f40d",
            "shortname": ":snake:",
            "code_decimal": "&#128013;",
            "category": "n",
            "emoji_order": "1403"
        },
        {
            "name": "dragon_face",
            "unicode": "1f432",
            "shortname": ":dragon_face:",
            "code_decimal": "&#128050;",
            "category": "n",
            "emoji_order": "1404"
        },
        {
            "name": "dragon",
            "unicode": "1f409",
            "shortname": ":dragon:",
            "code_decimal": "&#128009;",
            "category": "n",
            "emoji_order": "1405"
        },
        {
            "name": "whale",
            "unicode": "1f433",
            "shortname": ":whale:",
            "code_decimal": "&#128051;",
            "category": "n",
            "emoji_order": "1406"
        },
        {
            "name": "whale2",
            "unicode": "1f40b",
            "shortname": ":whale2:",
            "code_decimal": "&#128011;",
            "category": "n",
            "emoji_order": "1407"
        },
        {
            "name": "dolphin",
            "unicode": "1f42c",
            "shortname": ":dolphin:",
            "code_decimal": "&#128044;",
            "category": "n",
            "emoji_order": "1408"
        },
        {
            "name": "fish",
            "unicode": "1f41f",
            "shortname": ":fish:",
            "code_decimal": "&#128031;",
            "category": "n",
            "emoji_order": "1409"
        },
        {
            "name": "tropical_fish",
            "unicode": "1f420",
            "shortname": ":tropical_fish:",
            "code_decimal": "&#128032;",
            "category": "n",
            "emoji_order": "1410"
        },
        {
            "name": "blowfish",
            "unicode": "1f421",
            "shortname": ":blowfish:",
            "code_decimal": "&#128033;",
            "category": "n",
            "emoji_order": "1411"
        },
        /*{ //@todo not found on image
    "name": "shark",
    "unicode": "1f988",
    "shortname": ":shark:",
    "code_decimal": "&#129416;",
    "category": "n",
    "emoji_order": "1412"
  },*/
        {
            "name": "octopus",
            "unicode": "1f419",
            "shortname": ":octopus:",
            "code_decimal": "&#128025;",
            "category": "n",
            "emoji_order": "1413"
        },
        {
            "name": "shell",
            "unicode": "1f41a",
            "shortname": ":shell:",
            "code_decimal": "&#128026;",
            "category": "n",
            "emoji_order": "1414"
        },
        {
            "name": "crab",
            "unicode": "1f980",
            "shortname": ":crab:",
            "code_decimal": "&#129408;",
            "category": "n",
            "emoji_order": "1415"
        },
        /*{ //@todo not found on image
    "name": "shrimp",
    "unicode": "1f990",
    "shortname": ":shrimp:",
    "code_decimal": "&#129424;",
    "category": "n",
    "emoji_order": "1416"
  },*/
        /*{ //@todo not found on image
    "name": "squid",
    "unicode": "1f991",
    "shortname": ":squid:",
    "code_decimal": "&#129425;",
    "category": "n",
    "emoji_order": "1417"
  },*/
        /*{ //@todo not found on image
    "name": "butterfly",
    "unicode": "1f98b",
    "shortname": ":butterfly:",
    "code_decimal": "&#129419;",
    "category": "n",
    "emoji_order": "1418"
  },*/
        {
            "name": "snail",
            "unicode": "1f40c",
            "shortname": ":snail:",
            "code_decimal": "&#128012;",
            "category": "n",
            "emoji_order": "1419"
        },
        {
            "name": "bug",
            "unicode": "1f41b",
            "shortname": ":bug:",
            "code_decimal": "&#128027;",
            "category": "n",
            "emoji_order": "1420"
        },
        {
            "name": "ant",
            "unicode": "1f41c",
            "shortname": ":ant:",
            "code_decimal": "&#128028;",
            "category": "n",
            "emoji_order": "1421"
        },
        {
            "name": "bee",
            "unicode": "1f41d",
            "shortname": ":bee:",
            "code_decimal": "&#128029;",
            "category": "n",
            "emoji_order": "1422"
        },
        {
            "name": "beetle",
            "unicode": "1f41e",
            "shortname": ":beetle:",
            "code_decimal": "&#128030;",
            "category": "n",
            "emoji_order": "1423"
        },
        {
            "name": "spider",
            "unicode": "1f577",
            "shortname": ":spider:",
            "code_decimal": "&#128375;",
            "category": "n",
            "emoji_order": "1424"
        },
        {
            "name": "spider_web",
            "unicode": "1f578",
            "shortname": ":spider_web:",
            "code_decimal": "&#128376;",
            "category": "n",
            "emoji_order": "1425"
        },
        {
            "name": "scorpion",
            "unicode": "1f982",
            "shortname": ":scorpion:",
            "code_decimal": "&#129410;",
            "category": "n",
            "emoji_order": "1426"
        },
        {
            "name": "bouquet",
            "unicode": "1f490",
            "shortname": ":bouquet:",
            "code_decimal": "&#128144;",
            "category": "n",
            "emoji_order": "1427"
        },
        {
            "name": "cherry_blossom",
            "unicode": "1f338",
            "shortname": ":cherry_blossom:",
            "code_decimal": "&#127800;",
            "category": "n",
            "emoji_order": "1428"
        },
        {
            "name": "white_flower",
            "unicode": "1f4ae",
            "shortname": ":white_flower:",
            "code_decimal": "&#128174;",
            "category": "s",
            "emoji_order": "1429"
        },
        {
            "name": "rosette",
            "unicode": "1f3f5",
            "shortname": ":rosette:",
            "code_decimal": "&#127989;",
            "category": "n",
            "emoji_order": "1430"
        },
        {
            "name": "rose",
            "unicode": "1f339",
            "shortname": ":rose:",
            "code_decimal": "&#127801;",
            "category": "n",
            "emoji_order": "1431"
        },
        /*{ //@todo not found on image
    "name": "wilted_rose",
    "unicode": "1f940",
    "shortname": ":wilted_rose:",
    "code_decimal": "&#129344;",
    "category": "n",
    "emoji_order": "1432"
  },*/
        {
            "name": "hibiscus",
            "unicode": "1f33a",
            "shortname": ":hibiscus:",
            "code_decimal": "&#127802;",
            "category": "n",
            "emoji_order": "1433"
        },
        {
            "name": "sunflower",
            "unicode": "1f33b",
            "shortname": ":sunflower:",
            "code_decimal": "&#127803;",
            "category": "n",
            "emoji_order": "1434"
        },
        {
            "name": "blossom",
            "unicode": "1f33c",
            "shortname": ":blossom:",
            "code_decimal": "&#127804;",
            "category": "n",
            "emoji_order": "1435"
        },
        {
            "name": "tulip",
            "unicode": "1f337",
            "shortname": ":tulip:",
            "code_decimal": "&#127799;",
            "category": "n",
            "emoji_order": "1436"
        },
        {
            "name": "seedling",
            "unicode": "1f331",
            "shortname": ":seedling:",
            "code_decimal": "&#127793;",
            "category": "n",
            "emoji_order": "1437"
        },
        {
            "name": "evergreen_tree",
            "unicode": "1f332",
            "shortname": ":evergreen_tree:",
            "code_decimal": "&#127794;",
            "category": "n",
            "emoji_order": "1438"
        },
        {
            "name": "deciduous_tree",
            "unicode": "1f333",
            "shortname": ":deciduous_tree:",
            "code_decimal": "&#127795;",
            "category": "n",
            "emoji_order": "1439"
        },
        {
            "name": "palm_tree",
            "unicode": "1f334",
            "shortname": ":palm_tree:",
            "code_decimal": "&#127796;",
            "category": "n",
            "emoji_order": "1440"
        },
        {
            "name": "cactus",
            "unicode": "1f335",
            "shortname": ":cactus:",
            "code_decimal": "&#127797;",
            "category": "n",
            "emoji_order": "1441"
        },
        {
            "name": "ear_of_rice",
            "unicode": "1f33e",
            "shortname": ":ear_of_rice:",
            "code_decimal": "&#127806;",
            "category": "n",
            "emoji_order": "1442"
        },
        {
            "name": "herb",
            "unicode": "1f33f",
            "shortname": ":herb:",
            "code_decimal": "&#127807;",
            "category": "n",
            "emoji_order": "1443"
        },
        {
            "name": "shamrock",
            "unicode": "2618",
            "shortname": ":shamrock:",
            "code_decimal": "&#9752;",
            "category": "n",
            "emoji_order": "1444"
        },
        {
            "name": "four_leaf_clover",
            "unicode": "1f340",
            "shortname": ":four_leaf_clover:",
            "code_decimal": "&#127808;",
            "category": "n",
            "emoji_order": "1445"
        },
        {
            "name": "maple_leaf",
            "unicode": "1f341",
            "shortname": ":maple_leaf:",
            "code_decimal": "&#127809;",
            "category": "n",
            "emoji_order": "1446"
        },
        {
            "name": "fallen_leaf",
            "unicode": "1f342",
            "shortname": ":fallen_leaf:",
            "code_decimal": "&#127810;",
            "category": "n",
            "emoji_order": "1447"
        },
        {
            "name": "leaves",
            "unicode": "1f343",
            "shortname": ":leaves:",
            "code_decimal": "&#127811;",
            "category": "n",
            "emoji_order": "1448"
        },
        {
            "name": "grapes",
            "unicode": "1f347",
            "shortname": ":grapes:",
            "code_decimal": "&#127815;",
            "category": "d",
            "emoji_order": "1449"
        },
        {
            "name": "melon",
            "unicode": "1f348",
            "shortname": ":melon:",
            "code_decimal": "&#127816;",
            "category": "d",
            "emoji_order": "1450"
        },
        {
            "name": "watermelon",
            "unicode": "1f349",
            "shortname": ":watermelon:",
            "code_decimal": "&#127817;",
            "category": "d",
            "emoji_order": "1451"
        },
        {
            "name": "tangerine",
            "unicode": "1f34a",
            "shortname": ":tangerine:",
            "code_decimal": "&#127818;",
            "category": "d",
            "emoji_order": "1452"
        },
        {
            "name": "lemon",
            "unicode": "1f34b",
            "shortname": ":lemon:",
            "code_decimal": "&#127819;",
            "category": "d",
            "emoji_order": "1453"
        },
        {
            "name": "banana",
            "unicode": "1f34c",
            "shortname": ":banana:",
            "code_decimal": "&#127820;",
            "category": "d",
            "emoji_order": "1454"
        },
        {
            "name": "pineapple",
            "unicode": "1f34d",
            "shortname": ":pineapple:",
            "code_decimal": "&#127821;",
            "category": "d",
            "emoji_order": "1455"
        },
        {
            "name": "apple",
            "unicode": "1f34e",
            "shortname": ":apple:",
            "code_decimal": "&#127822;",
            "category": "d",
            "emoji_order": "1456"
        },
        {
            "name": "green_apple",
            "unicode": "1f34f",
            "shortname": ":green_apple:",
            "code_decimal": "&#127823;",
            "category": "d",
            "emoji_order": "1457"
        },
        {
            "name": "pear",
            "unicode": "1f350",
            "shortname": ":pear:",
            "code_decimal": "&#127824;",
            "category": "d",
            "emoji_order": "1458"
        },
        {
            "name": "peach",
            "unicode": "1f351",
            "shortname": ":peach:",
            "code_decimal": "&#127825;",
            "category": "d",
            "emoji_order": "1459"
        },
        {
            "name": "cherries",
            "unicode": "1f352",
            "shortname": ":cherries:",
            "code_decimal": "&#127826;",
            "category": "d",
            "emoji_order": "1460"
        },
        {
            "name": "strawberry",
            "unicode": "1f353",
            "shortname": ":strawberry:",
            "code_decimal": "&#127827;",
            "category": "d",
            "emoji_order": "1461"
        },
        /*{ //@todo not found on image
    "name": "kiwi",
    "unicode": "1f95d",
    "shortname": ":kiwi:",
    "code_decimal": "&#129373;",
    "category": "d",
    "emoji_order": "1462"
  },*/
        {
            "name": "tomato",
            "unicode": "1f345",
            "shortname": ":tomato:",
            "code_decimal": "&#127813;",
            "category": "d",
            "emoji_order": "1463"
        },
        /*{ //@todo not found on image
    "name": "avocado",
    "unicode": "1f951",
    "shortname": ":avocado:",
    "code_decimal": "&#129361;",
    "category": "d",
    "emoji_order": "1464"
  },*/
        {
            "name": "eggplant",
            "unicode": "1f346",
            "shortname": ":eggplant:",
            "code_decimal": "&#127814;",
            "category": "d",
            "emoji_order": "1465"
        },
        /*{ //@todo not found on image
    "name": "potato",
    "unicode": "1f954",
    "shortname": ":potato:",
    "code_decimal": "&#129364;",
    "category": "d",
    "emoji_order": "1466"
  },*/
        /*{ //@todo not found on image
    "name": "carrot",
    "unicode": "1f955",
    "shortname": ":carrot:",
    "code_decimal": "&#129365;",
    "category": "d",
    "emoji_order": "1467"
  },*/
        {
            "name": "corn",
            "unicode": "1f33d",
            "shortname": ":corn:",
            "code_decimal": "&#127805;",
            "category": "d",
            "emoji_order": "1468"
        },
        {
            "name": "hot_pepper",
            "unicode": "1f336",
            "shortname": ":hot_pepper:",
            "code_decimal": "&#127798;",
            "category": "d",
            "emoji_order": "1469"
        },
        /*{ //@todo not found on image
    "name": "cucumber",
    "unicode": "1f952",
    "shortname": ":cucumber:",
    "code_decimal": "&#129362;",
    "category": "d",
    "emoji_order": "1470"
  },*/
        {
            "name": "mushroom",
            "unicode": "1f344",
            "shortname": ":mushroom:",
            "code_decimal": "&#127812;",
            "category": "n",
            "emoji_order": "1471"
        },
        /*{ //@todo not found on image
    "name": "peanuts",
    "unicode": "1f95c",
    "shortname": ":peanuts:",
    "code_decimal": "&#129372;",
    "category": "d",
    "emoji_order": "1472"
  },*/
        {
            "name": "chestnut",
            "unicode": "1f330",
            "shortname": ":chestnut:",
            "code_decimal": "&#127792;",
            "category": "n",
            "emoji_order": "1473"
        },
        {
            "name": "bread",
            "unicode": "1f35e",
            "shortname": ":bread:",
            "code_decimal": "&#127838;",
            "category": "d",
            "emoji_order": "1474"
        },
        /*{ //@todo not found on image
    "name": "croissant",
    "unicode": "1f950",
    "shortname": ":croissant:",
    "code_decimal": "&#129360;",
    "category": "d",
    "emoji_order": "1475"
  },*/
        /*{ //@todo not found on image
    "name": "french_bread",
    "unicode": "1f956",
    "shortname": ":french_bread:",
    "code_decimal": "&#129366;",
    "category": "d",
    "emoji_order": "1476"
  },*/
        /*{ //@todo not found on image
    "name": "pancakes",
    "unicode": "1f95e",
    "shortname": ":pancakes:",
    "code_decimal": "&#129374;",
    "category": "d",
    "emoji_order": "1477"
  },*/
        {
            "name": "cheese_wedge",
            "unicode": "1f9c0",
            "shortname": ":cheese:",
            "code_decimal": "&#129472;",
            "category": "d",
            "emoji_order": "1478"
        },
        {
            "name": "meat_on_bone",
            "unicode": "1f356",
            "shortname": ":meat_on_bone:",
            "code_decimal": "&#127830;",
            "category": "d",
            "emoji_order": "1479"
        },
        {
            "name": "poultry_leg",
            "unicode": "1f357",
            "shortname": ":poultry_leg:",
            "code_decimal": "&#127831;",
            "category": "d",
            "emoji_order": "1480"
        },
        /*{ //@todo not found on image
    "name": "bacon",
    "unicode": "1f953",
    "shortname": ":bacon:",
    "code_decimal": "&#129363;",
    "category": "d",
    "emoji_order": "1481"
  },*/
        {
            "name": "hamburger",
            "unicode": "1f354",
            "shortname": ":hamburger:",
            "code_decimal": "&#127828;",
            "category": "d",
            "emoji_order": "1482"
        },
        {
            "name": "fries",
            "unicode": "1f35f",
            "shortname": ":fries:",
            "code_decimal": "&#127839;",
            "category": "d",
            "emoji_order": "1483"
        },
        {
            "name": "pizza",
            "unicode": "1f355",
            "shortname": ":pizza:",
            "code_decimal": "&#127829;",
            "category": "d",
            "emoji_order": "1484"
        },
        {
            "name": "hotdog",
            "unicode": "1f32d",
            "shortname": ":hotdog:",
            "code_decimal": "&#127789;",
            "category": "d",
            "emoji_order": "1485"
        },
        {
            "name": "taco",
            "unicode": "1f32e",
            "shortname": ":taco:",
            "code_decimal": "&#127790;",
            "category": "d",
            "emoji_order": "1486"
        },
        {
            "name": "burrito",
            "unicode": "1f32f",
            "shortname": ":burrito:",
            "code_decimal": "&#127791;",
            "category": "d",
            "emoji_order": "1487"
        },
        /*{ //@todo not found on image
    "name": "stuffed_flatbread",
    "unicode": "1f959",
    "shortname": ":stuffed_flatbread:",
    "code_decimal": "&#129369;",
    "category": "d",
    "emoji_order": "1488"
  },*/
        {
            "name": "egg",
            "unicode": "1f95a",
            "shortname": ":egg:",
            "code_decimal": "&#129370;",
            "category": "d",
            "emoji_order": "1489"
        },
        /*{ //@todo not found on image
    "name": "cooking",
    "unicode": "1f373",
    "shortname": ":cooking:",
    "code_decimal": "&#127859;",
    "category": "d",
    "emoji_order": "1490"
  },*/
        /*{ //@todo not found on image
    "name": "shallow_pan_of_f",
    "unicode": "1f958",
    "shortname": ":shallow_pan_of_f:",
    "code_decimal": "&#129368;",
    "category": "d",
    "emoji_order": "1491"
  },*/
        {
            "name": "stew",
            "unicode": "1f372",
            "shortname": ":stew:",
            "code_decimal": "&#127858;",
            "category": "d",
            "emoji_order": "1492"
        },
        /*{ //@todo not found on image
    "name": "salad",
    "unicode": "1f957",
    "shortname": ":salad:",
    "code_decimal": "&#129367;",
    "category": "d",
    "emoji_order": "1493"
  },*/
        {
            "name": "popcorn",
            "unicode": "1f37f",
            "shortname": ":popcorn:",
            "code_decimal": "&#127871;",
            "category": "d",
            "emoji_order": "1494"
        },
        {
            "name": "bento",
            "unicode": "1f371",
            "shortname": ":bento:",
            "code_decimal": "&#127857;",
            "category": "d",
            "emoji_order": "1495"
        },
        {
            "name": "rice_cracker",
            "unicode": "1f358",
            "shortname": ":rice_cracker:",
            "code_decimal": "&#127832;",
            "category": "d",
            "emoji_order": "1496"
        },
        {
            "name": "rice_ball",
            "unicode": "1f359",
            "shortname": ":rice_ball:",
            "code_decimal": "&#127833;",
            "category": "d",
            "emoji_order": "1497"
        },
        {
            "name": "rice",
            "unicode": "1f35a",
            "shortname": ":rice:",
            "code_decimal": "&#127834;",
            "category": "d",
            "emoji_order": "1498"
        },
        {
            "name": "curry",
            "unicode": "1f35b",
            "shortname": ":curry:",
            "code_decimal": "&#127835;",
            "category": "d",
            "emoji_order": "1499"
        },
        {
            "name": "ramen",
            "unicode": "1f35c",
            "shortname": ":ramen:",
            "code_decimal": "&#127836;",
            "category": "d",
            "emoji_order": "1500"
        },
        {
            "name": "spaghetti",
            "unicode": "1f35d",
            "shortname": ":spaghetti:",
            "code_decimal": "&#127837;",
            "category": "d",
            "emoji_order": "1501"
        },
        {
            "name": "sweet_potato",
            "unicode": "1f360",
            "shortname": ":sweet_potato:",
            "code_decimal": "&#127840;",
            "category": "d",
            "emoji_order": "1502"
        },
        {
            "name": "oden",
            "unicode": "1f362",
            "shortname": ":oden:",
            "code_decimal": "&#127842;",
            "category": "d",
            "emoji_order": "1503"
        },
        {
            "name": "sushi",
            "unicode": "1f363",
            "shortname": ":sushi:",
            "code_decimal": "&#127843;",
            "category": "d",
            "emoji_order": "1504"
        },
        {
            "name": "fried_shrimp",
            "unicode": "1f364",
            "shortname": ":fried_shrimp:",
            "code_decimal": "&#127844;",
            "category": "d",
            "emoji_order": "1505"
        },
        {
            "name": "fish_cake",
            "unicode": "1f365",
            "shortname": ":fish_cake:",
            "code_decimal": "&#127845;",
            "category": "d",
            "emoji_order": "1506"
        },
        {
            "name": "dango",
            "unicode": "1f361",
            "shortname": ":dango:",
            "code_decimal": "&#127841;",
            "category": "d",
            "emoji_order": "1507"
        },
        {
            "name": "icecream",
            "unicode": "1f366",
            "shortname": ":icecream:",
            "code_decimal": "&#127846;",
            "category": "d",
            "emoji_order": "1508"
        },
        {
            "name": "shaved_ice",
            "unicode": "1f367",
            "shortname": ":shaved_ice:",
            "code_decimal": "&#127847;",
            "category": "d",
            "emoji_order": "1509"
        },
        {
            "name": "ice_cream",
            "unicode": "1f368",
            "shortname": ":ice_cream:",
            "code_decimal": "&#127848;",
            "category": "d",
            "emoji_order": "1510"
        },
        {
            "name": "doughnut",
            "unicode": "1f369",
            "shortname": ":doughnut:",
            "code_decimal": "&#127849;",
            "category": "d",
            "emoji_order": "1511"
        },
        {
            "name": "cookie",
            "unicode": "1f36a",
            "shortname": ":cookie:",
            "code_decimal": "&#127850;",
            "category": "d",
            "emoji_order": "1512"
        },
        {
            "name": "birthday",
            "unicode": "1f382",
            "shortname": ":birthday:",
            "code_decimal": "&#127874;",
            "category": "d",
            "emoji_order": "1513"
        },
        {
            "name": "cake",
            "unicode": "1f370",
            "shortname": ":cake:",
            "code_decimal": "&#127856;",
            "category": "d",
            "emoji_order": "1514"
        },
        {
            "name": "chocolate_bar",
            "unicode": "1f36b",
            "shortname": ":chocolate_bar:",
            "code_decimal": "&#127851;",
            "category": "d",
            "emoji_order": "1515"
        },
        {
            "name": "candy",
            "unicode": "1f36c",
            "shortname": ":candy:",
            "code_decimal": "&#127852;",
            "category": "d",
            "emoji_order": "1516"
        },
        {
            "name": "lollipop",
            "unicode": "1f36d",
            "shortname": ":lollipop:",
            "code_decimal": "&#127853;",
            "category": "d",
            "emoji_order": "1517"
        },
        {
            "name": "custard",
            "unicode": "1f36e",
            "shortname": ":custard:",
            "code_decimal": "&#127854;",
            "category": "d",
            "emoji_order": "1518"
        },
        {
            "name": "honey_pot",
            "unicode": "1f36f",
            "shortname": ":honey_pot:",
            "code_decimal": "&#127855;",
            "category": "d",
            "emoji_order": "1519"
        },
        {
            "name": "baby_bottle",
            "unicode": "1f37c",
            "shortname": ":baby_bottle:",
            "code_decimal": "&#127868;",
            "category": "d",
            "emoji_order": "1520"
        },
        /*{ //@todo not found on image
    "name": "milk",
    "unicode": "1f95b",
    "shortname": ":milk:",
    "code_decimal": "&#129371;",
    "category": "d",
    "emoji_order": "1521"
  },*/
        {
            "name": "coffee",
            "unicode": "2615",
            "shortname": ":coffee:",
            "code_decimal": "&#9749;",
            "category": "d",
            "emoji_order": "1522"
        },
        {
            "name": "tea",
            "unicode": "1f375",
            "shortname": ":tea:",
            "code_decimal": "&#127861;",
            "category": "d",
            "emoji_order": "1523"
        },
        {
            "name": "sake",
            "unicode": "1f376",
            "shortname": ":sake:",
            "code_decimal": "&#127862;",
            "category": "d",
            "emoji_order": "1524"
        },
        {
            "name": "champagne",
            "unicode": "1f37e",
            "shortname": ":champagne:",
            "code_decimal": "&#127870;",
            "category": "d",
            "emoji_order": "1525"
        },
        {
            "name": "wine_glass",
            "unicode": "1f377",
            "shortname": ":wine_glass:",
            "code_decimal": "&#127863;",
            "category": "d",
            "emoji_order": "1526"
        },
        {
            "name": "cocktail",
            "unicode": "1f378",
            "shortname": ":cocktail:",
            "code_decimal": "&#127864;",
            "category": "d",
            "emoji_order": "1527"
        },
        {
            "name": "tropical_drink",
            "unicode": "1f379",
            "shortname": ":tropical_drink:",
            "code_decimal": "&#127865;",
            "category": "d",
            "emoji_order": "1528"
        },
        {
            "name": "beer",
            "unicode": "1f37a",
            "shortname": ":beer:",
            "code_decimal": "&#127866;",
            "category": "d",
            "emoji_order": "1529"
        },
        {
            "name": "beers",
            "unicode": "1f37b",
            "shortname": ":beers:",
            "code_decimal": "&#127867;",
            "category": "d",
            "emoji_order": "1530"
        },
        /*{ //@todo not found on image
    "name": "champagne_glass",
    "unicode": "1f942",
    "shortname": ":champagne_glass:",
    "code_decimal": "&#129346;",
    "category": "d",
    "emoji_order": "1531"
  },*/
        /*{ //@todo not found on image
    "name": "tumbler_glass",
    "unicode": "1f943",
    "shortname": ":tumbler_glass:",
    "code_decimal": "&#129347;",
    "category": "d",
    "emoji_order": "1532"
  },*/
        {
            "name": "knife_fork_plate",
            "unicode": "1f37d",
            "shortname": ":fork_knife_plate:",
            "code_decimal": "&#127869;",
            "category": "d",
            "emoji_order": "1533"
        },
        {
            "name": "fork_and_knife",
            "unicode": "1f374",
            "shortname": ":fork_and_knife:",
            "code_decimal": "&#127860;",
            "category": "d",
            "emoji_order": "1534"
        },
        /*{ //@todo not found on image
    "name": "spoon",
    "unicode": "1f944",
    "shortname": ":spoon:",
    "code_decimal": "&#129348;",
    "category": "d",
    "emoji_order": "1535"
  },*/
        /*{ //@todo not found on image
    "name": "knife",
    "unicode": "1f52a",
    "shortname": ":knife:",
    "code_decimal": "&#128298;",
    "category": "o",
    "emoji_order": "1536"
  },*/
        {
            "name": "amphora",
            "unicode": "1f3fa",
            "shortname": ":amphora:",
            "code_decimal": "&#127994;",
            "category": "o",
            "emoji_order": "1537"
        },
        {
            "name": "earth_africa",
            "unicode": "1f30d",
            "shortname": ":earth_africa:",
            "code_decimal": "&#127757;",
            "category": "n",
            "emoji_order": "1538"
        },
        {
            "name": "earth_americas",
            "unicode": "1f30e",
            "shortname": ":earth_americas:",
            "code_decimal": "&#127758;",
            "category": "n",
            "emoji_order": "1539"
        },
        {
            "name": "earth_asia",
            "unicode": "1f30f",
            "shortname": ":earth_asia:",
            "code_decimal": "&#127759;",
            "category": "n",
            "emoji_order": "1540"
        },
        {
            "name": "globe_with_meridians",
            "unicode": "1f310",
            "shortname": ":globe_with_meridians:",
            "code_decimal": "&#127760;",
            "category": "s",
            "emoji_order": "1541"
        },
        {
            "name": "world_map",
            "unicode": "1f5fa",
            "shortname": ":map:",
            "code_decimal": "&#128506;",
            "category": "o",
            "emoji_order": "1542"
        },
        {
            "name": "japan",
            "unicode": "1f5fe",
            "shortname": ":japan:",
            "code_decimal": "&#128510;",
            "category": "t",
            "emoji_order": "1543"
        },
        {
            "name": "snow_capped_mountain",
            "unicode": "1f3d4",
            "shortname": ":mountain_snow:",
            "code_decimal": "&#127956;",
            "category": "t",
            "emoji_order": "1544"
        },
        {
            "name": "mountain",
            "unicode": "26f0",
            "shortname": ":mountain:",
            "code_decimal": "&#9968;",
            "category": "t",
            "emoji_order": "1545"
        },
        {
            "name": "volcano",
            "unicode": "1f30b",
            "shortname": ":volcano:",
            "code_decimal": "&#127755;",
            "category": "t",
            "emoji_order": "1546"
        },
        {
            "name": "mount_fuji",
            "unicode": "1f5fb",
            "shortname": ":mount_fuji:",
            "code_decimal": "&#128507;",
            "category": "t",
            "emoji_order": "1547"
        },
        {
            "name": "camping",
            "unicode": "1f3d5",
            "shortname": ":camping:",
            "code_decimal": "&#127957;",
            "category": "t",
            "emoji_order": "1548"
        },
        {
            "name": "beach_with_umbrella",
            "unicode": "1f3d6",
            "shortname": ":beach:",
            "code_decimal": "&#127958;",
            "category": "t",
            "emoji_order": "1549"
        },
        {
            "name": "desert",
            "unicode": "1f3dc",
            "shortname": ":desert:",
            "code_decimal": "&#127964;",
            "category": "t",
            "emoji_order": "1550"
        },
        {
            "name": "desert_island",
            "unicode": "1f3dd",
            "shortname": ":island:",
            "code_decimal": "&#127965;",
            "category": "t",
            "emoji_order": "1551"
        },
        {
            "name": "national_park",
            "unicode": "1f3de",
            "shortname": ":park:",
            "code_decimal": "&#127966;",
            "category": "t",
            "emoji_order": "1552"
        },
        {
            "name": "stadium",
            "unicode": "1f3df",
            "shortname": ":stadium:",
            "code_decimal": "&#127967;",
            "category": "t",
            "emoji_order": "1553"
        },
        {
            "name": "classical_building",
            "unicode": "1f3db",
            "shortname": ":classical_building:",
            "code_decimal": "&#127963;",
            "category": "t",
            "emoji_order": "1554"
        },
        {
            "name": "building_construction",
            "unicode": "1f3d7",
            "shortname": ":construction_site:",
            "code_decimal": "&#127959;",
            "category": "t",
            "emoji_order": "1555"
        },
        {
            "name": "house_buildings",
            "unicode": "1f3d8",
            "shortname": ":homes:",
            "code_decimal": "&#127960;",
            "category": "t",
            "emoji_order": "1556"
        },
        {
            "name": "cityscape",
            "unicode": "1f3d9",
            "shortname": ":cityscape:",
            "code_decimal": "&#127961;",
            "category": "t",
            "emoji_order": "1557"
        },
        {
            "name": "derelict_house_building",
            "unicode": "1f3da",
            "shortname": ":house_abandoned:",
            "code_decimal": "&#127962;",
            "category": "t",
            "emoji_order": "1558"
        },
        {
            "name": "house",
            "unicode": "1f3e0",
            "shortname": ":house:",
            "code_decimal": "&#127968;",
            "category": "t",
            "emoji_order": "1559"
        },
        {
            "name": "house_with_garden",
            "unicode": "1f3e1",
            "shortname": ":house_with_garden:",
            "code_decimal": "&#127969;",
            "category": "t",
            "emoji_order": "1560"
        },
        {
            "name": "office",
            "unicode": "1f3e2",
            "shortname": ":office:",
            "code_decimal": "&#127970;",
            "category": "t",
            "emoji_order": "1561"
        },
        {
            "name": "post_office",
            "unicode": "1f3e3",
            "shortname": ":post_office:",
            "code_decimal": "&#127971;",
            "category": "t",
            "emoji_order": "1562"
        },
        {
            "name": "european_post_office",
            "unicode": "1f3e4",
            "shortname": ":european_post_office:",
            "code_decimal": "&#127972;",
            "category": "t",
            "emoji_order": "1563"
        },
        {
            "name": "hospital",
            "unicode": "1f3e5",
            "shortname": ":hospital:",
            "code_decimal": "&#127973;",
            "category": "t",
            "emoji_order": "1564"
        },
        {
            "name": "bank",
            "unicode": "1f3e6",
            "shortname": ":bank:",
            "code_decimal": "&#127974;",
            "category": "t",
            "emoji_order": "1565"
        },
        {
            "name": "hotel",
            "unicode": "1f3e8",
            "shortname": ":hotel:",
            "code_decimal": "&#127976;",
            "category": "t",
            "emoji_order": "1566"
        },
        {
            "name": "love_hotel",
            "unicode": "1f3e9",
            "shortname": ":love_hotel:",
            "code_decimal": "&#127977;",
            "category": "t",
            "emoji_order": "1567"
        },
        {
            "name": "convenience_store",
            "unicode": "1f3ea",
            "shortname": ":convenience_store:",
            "code_decimal": "&#127978;",
            "category": "t",
            "emoji_order": "1568"
        },
        {
            "name": "school",
            "unicode": "1f3eb",
            "shortname": ":school:",
            "code_decimal": "&#127979;",
            "category": "t",
            "emoji_order": "1569"
        },
        {
            "name": "department_store",
            "unicode": "1f3ec",
            "shortname": ":department_store:",
            "code_decimal": "&#127980;",
            "category": "t",
            "emoji_order": "1570"
        },
        {
            "name": "factory",
            "unicode": "1f3ed",
            "shortname": ":factory:",
            "code_decimal": "&#127981;",
            "category": "t",
            "emoji_order": "1571"
        },
        {
            "name": "japanese_castle",
            "unicode": "1f3ef",
            "shortname": ":japanese_castle:",
            "code_decimal": "&#127983;",
            "category": "t",
            "emoji_order": "1572"
        },
        {
            "name": "european_castle",
            "unicode": "1f3f0",
            "shortname": ":european_castle:",
            "code_decimal": "&#127984;",
            "category": "t",
            "emoji_order": "1573"
        },
        {
            "name": "wedding",
            "unicode": "1f492",
            "shortname": ":wedding:",
            "code_decimal": "&#128146;",
            "category": "t",
            "emoji_order": "1574"
        },
        {
            "name": "tokyo_tower",
            "unicode": "1f5fc",
            "shortname": ":tokyo_tower:",
            "code_decimal": "&#128508;",
            "category": "t",
            "emoji_order": "1575"
        },
        {
            "name": "statue_of_liberty",
            "unicode": "1f5fd",
            "shortname": ":statue_of_liberty:",
            "code_decimal": "&#128509;",
            "category": "t",
            "emoji_order": "1576"
        },
        {
            "name": "church",
            "unicode": "26ea",
            "shortname": ":church:",
            "code_decimal": "&#9962;",
            "category": "t",
            "emoji_order": "1577"
        },
        {
            "name": "mosque",
            "unicode": "1f54c",
            "shortname": ":mosque:",
            "code_decimal": "&#128332;",
            "category": "t",
            "emoji_order": "1578"
        },
        {
            "name": "synagogue",
            "unicode": "1f54d",
            "shortname": ":synagogue:",
            "code_decimal": "&#128333;",
            "category": "t",
            "emoji_order": "1579"
        },
        {
            "name": "shinto_shrine",
            "unicode": "26e9",
            "shortname": ":shinto_shrine:",
            "code_decimal": "&#9961;",
            "category": "t",
            "emoji_order": "1580"
        },
        {
            "name": "kaaba",
            "unicode": "1f54b",
            "shortname": ":kaaba:",
            "code_decimal": "&#128331;",
            "category": "t",
            "emoji_order": "1581"
        },
        {
            "name": "fountain",
            "unicode": "26f2",
            "shortname": ":fountain:",
            "code_decimal": "&#9970;",
            "category": "t",
            "emoji_order": "1582"
        },
        {
            "name": "tent",
            "unicode": "26fa",
            "shortname": ":tent:",
            "code_decimal": "&#9978;",
            "category": "t",
            "emoji_order": "1583"
        },
        {
            "name": "foggy",
            "unicode": "1f301",
            "shortname": ":foggy:",
            "code_decimal": "&#127745;",
            "category": "t",
            "emoji_order": "1584"
        },
        {
            "name": "night_with_stars",
            "unicode": "1f303",
            "shortname": ":night_with_stars:",
            "code_decimal": "&#127747;",
            "category": "t",
            "emoji_order": "1585"
        },
        {
            "name": "sunrise_over_mountains",
            "unicode": "1f304",
            "shortname": ":sunrise_over_mountains:",
            "code_decimal": "&#127748;",
            "category": "t",
            "emoji_order": "1586"
        },
        {
            "name": "sunrise",
            "unicode": "1f305",
            "shortname": ":sunrise:",
            "code_decimal": "&#127749;",
            "category": "t",
            "emoji_order": "1587"
        },
        /*{ //@todo not found on image
    "name": "city_dusk",
    "unicode": "1f306",
    "shortname": ":city_dusk:",
    "code_decimal": "&#127750;",
    "category": "t",
    "emoji_order": "1588"
  },*/
        {
            "name": "city_sunset",
            "unicode": "1f307",
            "shortname": ":city_sunset:",
            "code_decimal": "&#127751;",
            "category": "t",
            "emoji_order": "1589"
        },
        {
            "name": "bridge_at_night",
            "unicode": "1f309",
            "shortname": ":bridge_at_night:",
            "code_decimal": "&#127753;",
            "category": "t",
            "emoji_order": "1590"
        },
        {
            "name": "hotsprings",
            "unicode": "2668",
            "shortname": ":hotsprings:",
            "code_decimal": "&#9832;",
            "category": "s",
            "emoji_order": "1591"
        },
        {
            "name": "milky_way",
            "unicode": "1f30c",
            "shortname": ":milky_way:",
            "code_decimal": "&#127756;",
            "category": "t",
            "emoji_order": "1592"
        },
        {
            "name": "carousel_horse",
            "unicode": "1f3a0",
            "shortname": ":carousel_horse:",
            "code_decimal": "&#127904;",
            "category": "t",
            "emoji_order": "1593"
        },
        {
            "name": "ferris_wheel",
            "unicode": "1f3a1",
            "shortname": ":ferris_wheel:",
            "code_decimal": "&#127905;",
            "category": "t",
            "emoji_order": "1594"
        },
        {
            "name": "roller_coaster",
            "unicode": "1f3a2",
            "shortname": ":roller_coaster:",
            "code_decimal": "&#127906;",
            "category": "t",
            "emoji_order": "1595"
        },
        {
            "name": "barber",
            "unicode": "1f488",
            "shortname": ":barber:",
            "code_decimal": "&#128136;",
            "category": "o",
            "emoji_order": "1596"
        },
        {
            "name": "circus_tent",
            "unicode": "1f3aa",
            "shortname": ":circus_tent:",
            "code_decimal": "&#127914;",
            "category": "a",
            "emoji_order": "1597"
        },
        {
            "name": "performing_arts",
            "unicode": "1f3ad",
            "shortname": ":performing_arts:",
            "code_decimal": "&#127917;",
            "category": "a",
            "emoji_order": "1598"
        },
        {
            "name": "frame_with_picture",
            "unicode": "1f5bc",
            "shortname": ":frame_photo:",
            "code_decimal": "&#128444;",
            "category": "o",
            "emoji_order": "1599"
        },
        {
            "name": "art",
            "unicode": "1f3a8",
            "shortname": ":art:",
            "code_decimal": "&#127912;",
            "category": "a",
            "emoji_order": "1600"
        },
        {
            "name": "slot_machine",
            "unicode": "1f3b0",
            "shortname": ":slot_machine:",
            "code_decimal": "&#127920;",
            "category": "a",
            "emoji_order": "1601"
        },
        {
            "name": "steam_locomotive",
            "unicode": "1f682",
            "shortname": ":steam_locomotive:",
            "code_decimal": "&#128642;",
            "category": "t",
            "emoji_order": "1602"
        },
        {
            "name": "railway_car",
            "unicode": "1f683",
            "shortname": ":railway_car:",
            "code_decimal": "&#128643;",
            "category": "t",
            "emoji_order": "1603"
        },
        {
            "name": "bullettrain_side",
            "unicode": "1f684",
            "shortname": ":bullettrain_side:",
            "code_decimal": "&#128644;",
            "category": "t",
            "emoji_order": "1604"
        },
        {
            "name": "bullettrain_front",
            "unicode": "1f685",
            "shortname": ":bullettrain_front:",
            "code_decimal": "&#128645;",
            "category": "t",
            "emoji_order": "1605"
        },
        {
            "name": "train2",
            "unicode": "1f686",
            "shortname": ":train2:",
            "code_decimal": "&#128646;",
            "category": "t",
            "emoji_order": "1606"
        },
        {
            "name": "metro",
            "unicode": "1f687",
            "shortname": ":metro:",
            "code_decimal": "&#128647;",
            "category": "t",
            "emoji_order": "1607"
        },
        {
            "name": "light_rail",
            "unicode": "1f688",
            "shortname": ":light_rail:",
            "code_decimal": "&#128648;",
            "category": "t",
            "emoji_order": "1608"
        },
        {
            "name": "station",
            "unicode": "1f689",
            "shortname": ":station:",
            "code_decimal": "&#128649;",
            "category": "t",
            "emoji_order": "1609"
        },
        {
            "name": "tram",
            "unicode": "1f68a",
            "shortname": ":tram:",
            "code_decimal": "&#128650;",
            "category": "t",
            "emoji_order": "1610"
        },
        {
            "name": "monorail",
            "unicode": "1f69d",
            "shortname": ":monorail:",
            "code_decimal": "&#128669;",
            "category": "t",
            "emoji_order": "1611"
        },
        {
            "name": "mountain_railway",
            "unicode": "1f69e",
            "shortname": ":mountain_railway:",
            "code_decimal": "&#128670;",
            "category": "t",
            "emoji_order": "1612"
        },
        {
            "name": "train",
            "unicode": "1f68b",
            "shortname": ":train:",
            "code_decimal": "&#128651;",
            "category": "t",
            "emoji_order": "1613"
        },
        {
            "name": "bus",
            "unicode": "1f68c",
            "shortname": ":bus:",
            "code_decimal": "&#128652;",
            "category": "t",
            "emoji_order": "1614"
        },
        {
            "name": "oncoming_bus",
            "unicode": "1f68d",
            "shortname": ":oncoming_bus:",
            "code_decimal": "&#128653;",
            "category": "t",
            "emoji_order": "1615"
        },
        {
            "name": "trolleybus",
            "unicode": "1f68e",
            "shortname": ":trolleybus:",
            "code_decimal": "&#128654;",
            "category": "t",
            "emoji_order": "1616"
        },
        {
            "name": "minibus",
            "unicode": "1f690",
            "shortname": ":minibus:",
            "code_decimal": "&#128656;",
            "category": "t",
            "emoji_order": "1617"
        },
        {
            "name": "ambulance",
            "unicode": "1f691",
            "shortname": ":ambulance:",
            "code_decimal": "&#128657;",
            "category": "t",
            "emoji_order": "1618"
        },
        {
            "name": "fire_engine",
            "unicode": "1f692",
            "shortname": ":fire_engine:",
            "code_decimal": "&#128658;",
            "category": "t",
            "emoji_order": "1619"
        },
        {
            "name": "police_car",
            "unicode": "1f693",
            "shortname": ":police_car:",
            "code_decimal": "&#128659;",
            "category": "t",
            "emoji_order": "1620"
        },
        {
            "name": "oncoming_police_car",
            "unicode": "1f694",
            "shortname": ":oncoming_police_car:",
            "code_decimal": "&#128660;",
            "category": "t",
            "emoji_order": "1621"
        },
        {
            "name": "taxi",
            "unicode": "1f695",
            "shortname": ":taxi:",
            "code_decimal": "&#128661;",
            "category": "t",
            "emoji_order": "1622"
        },
        {
            "name": "oncoming_taxi",
            "unicode": "1f696",
            "shortname": ":oncoming_taxi:",
            "code_decimal": "&#128662;",
            "category": "t",
            "emoji_order": "1623"
        },
        {
            "name": "car",
            "unicode": "1f697",
            "shortname": ":red_car:",
            "code_decimal": "&#128663;",
            "category": "t",
            "emoji_order": "1624"
        },
        {
            "name": "oncoming_automobile",
            "unicode": "1f698",
            "shortname": ":oncoming_automobile:",
            "code_decimal": "&#128664;",
            "category": "t",
            "emoji_order": "1625"
        },
        {
            "name": "blue_car",
            "unicode": "1f699",
            "shortname": ":blue_car:",
            "code_decimal": "&#128665;",
            "category": "t",
            "emoji_order": "1626"
        },
        {
            "name": "truck",
            "unicode": "1f69a",
            "shortname": ":truck:",
            "code_decimal": "&#128666;",
            "category": "t",
            "emoji_order": "1627"
        },
        {
            "name": "articulated_lorry",
            "unicode": "1f69b",
            "shortname": ":articulated_lorry:",
            "code_decimal": "&#128667;",
            "category": "t",
            "emoji_order": "1628"
        },
        {
            "name": "tractor",
            "unicode": "1f69c",
            "shortname": ":tractor:",
            "code_decimal": "&#128668;",
            "category": "t",
            "emoji_order": "1629"
        },
        {
            "name": "bike",
            "unicode": "1f6b2",
            "shortname": ":bike:",
            "code_decimal": "&#128690;",
            "category": "t",
            "emoji_order": "1630"
        },
        /*{ //@todo not found on image
    "name": "scooter",
    "unicode": "1f6f4",
    "shortname": ":scooter:",
    "code_decimal": "&#128756;",
    "category": "t",
    "emoji_order": "1631"
  },*/
        /*{ //@todo not found on image
    "name": "motor_scooter",
    "unicode": "1f6f5",
    "shortname": ":motor_scooter:",
    "code_decimal": "&#128757;",
    "category": "t",
    "emoji_order": "1632"
  },*/
        {
            "name": "busstop",
            "unicode": "1f68f",
            "shortname": ":busstop:",
            "code_decimal": "&#128655;",
            "category": "t",
            "emoji_order": "1633"
        },
        {
            "name": "motorway",
            "unicode": "1f6e3",
            "shortname": ":motorway:",
            "code_decimal": "&#128739;",
            "category": "t",
            "emoji_order": "1634"
        },
        {
            "name": "railway_track",
            "unicode": "1f6e4",
            "shortname": ":railway_track:",
            "code_decimal": "&#128740;",
            "category": "t",
            "emoji_order": "1635"
        },
        {
            "name": "fuelpump",
            "unicode": "26fd",
            "shortname": ":fuelpump:",
            "code_decimal": "&#9981;",
            "category": "t",
            "emoji_order": "1636"
        },
        {
            "name": "rotating_light",
            "unicode": "1f6a8",
            "shortname": ":rotating_light:",
            "code_decimal": "&#128680;",
            "category": "t",
            "emoji_order": "1637"
        },
        {
            "name": "traffic_light",
            "unicode": "1f6a5",
            "shortname": ":traffic_light:",
            "code_decimal": "&#128677;",
            "category": "t",
            "emoji_order": "1638"
        },
        {
            "name": "vertical_traffic_light",
            "unicode": "1f6a6",
            "shortname": ":vertical_traffic_light:",
            "code_decimal": "&#128678;",
            "category": "t",
            "emoji_order": "1639"
        },
        {
            "name": "construction",
            "unicode": "1f6a7",
            "shortname": ":construction:",
            "code_decimal": "&#128679;",
            "category": "t",
            "emoji_order": "1640"
        },
        {
            "name": "octagonal_sign",
            "unicode": "1f6d1",
            "shortname": ":octagonal_sign:",
            "code_decimal": "&#128721;",
            "category": "s",
            "emoji_order": "1641"
        },
        {
            "name": "anchor",
            "unicode": "2693",
            "shortname": ":anchor:",
            "code_decimal": "&#9875;",
            "category": "t",
            "emoji_order": "1642"
        },
        {
            "name": "boat",
            "unicode": "26f5",
            "shortname": ":sailboat:",
            "code_decimal": "&#9973;",
            "category": "t",
            "emoji_order": "1643"
        },
        /*{ //@todo not found on image
    "name": "canoe",
    "unicode": "1f6f6",
    "shortname": ":canoe:",
    "code_decimal": "&#128758;",
    "category": "t",
    "emoji_order": "1644"
  },*/
        {
            "name": "speedboat",
            "unicode": "1f6a4",
            "shortname": ":speedboat:",
            "code_decimal": "&#128676;",
            "category": "t",
            "emoji_order": "1645"
        },
        {
            "name": "passenger_ship",
            "unicode": "1f6f3",
            "shortname": ":cruise_ship:",
            "code_decimal": "&#128755;",
            "category": "t",
            "emoji_order": "1646"
        },
        {
            "name": "ferry",
            "unicode": "26f4",
            "shortname": ":ferry:",
            "code_decimal": "&#9972;",
            "category": "t",
            "emoji_order": "1647"
        },
        {
            "name": "motor_boat",
            "unicode": "1f6e5",
            "shortname": ":motorboat:",
            "code_decimal": "&#128741;",
            "category": "t",
            "emoji_order": "1648"
        },
        {
            "name": "ship",
            "unicode": "1f6a2",
            "shortname": ":ship:",
            "code_decimal": "&#128674;",
            "category": "t",
            "emoji_order": "1649"
        },
        {
            "name": "airplane",
            "unicode": "2708",
            "shortname": ":airplane:",
            "code_decimal": "&#9992;",
            "category": "t",
            "emoji_order": "1650"
        },
        {
            "name": "small_airplane",
            "unicode": "1f6e9",
            "shortname": ":airplane_small:",
            "code_decimal": "&#128745;",
            "category": "t",
            "emoji_order": "1651"
        },
        {
            "name": "airplane_departure",
            "unicode": "1f6eb",
            "shortname": ":airplane_departure:",
            "code_decimal": "&#128747;",
            "category": "t",
            "emoji_order": "1652"
        },
        {
            "name": "airplane_arriving",
            "unicode": "1f6ec",
            "shortname": ":airplane_arriving:",
            "code_decimal": "&#128748;",
            "category": "t",
            "emoji_order": "1653"
        },
        {
            "name": "seat",
            "unicode": "1f4ba",
            "shortname": ":seat:",
            "code_decimal": "&#128186;",
            "category": "t",
            "emoji_order": "1654"
        },
        {
            "name": "helicopter",
            "unicode": "1f681",
            "shortname": ":helicopter:",
            "code_decimal": "&#128641;",
            "category": "t",
            "emoji_order": "1655"
        },
        {
            "name": "suspension_railway",
            "unicode": "1f69f",
            "shortname": ":suspension_railway:",
            "code_decimal": "&#128671;",
            "category": "t",
            "emoji_order": "1656"
        },
        {
            "name": "mountain_cableway",
            "unicode": "1f6a0",
            "shortname": ":mountain_cableway:",
            "code_decimal": "&#128672;",
            "category": "t",
            "emoji_order": "1657"
        },
        {
            "name": "aerial_tramway",
            "unicode": "1f6a1",
            "shortname": ":aerial_tramway:",
            "code_decimal": "&#128673;",
            "category": "t",
            "emoji_order": "1658"
        },
        {
            "name": "rocket",
            "unicode": "1f680",
            "shortname": ":rocket:",
            "code_decimal": "&#128640;",
            "category": "t",
            "emoji_order": "1659"
        },
        {
            "name": "satellite",
            "unicode": "1f6f0",
            "shortname": ":satellite_orbital:",
            "code_decimal": "&#128752;",
            "category": "t",
            "emoji_order": "1660"
        },
        {
            "name": "bellhop_bell",
            "unicode": "1f6ce",
            "shortname": ":bellhop:",
            "code_decimal": "&#128718;",
            "category": "o",
            "emoji_order": "1661"
        },
        {
            "name": "door",
            "unicode": "1f6aa",
            "shortname": ":door:",
            "code_decimal": "&#128682;",
            "category": "o",
            "emoji_order": "1662"
        },
        {
            "name": "sleeping_accommodation",
            "unicode": "1f6cc",
            "shortname": ":sleeping_accommodation:",
            "code_decimal": "&#128716;",
            "category": "o",
            "emoji_order": "1663"
        },
        {
            "name": "bed",
            "unicode": "1f6cf",
            "shortname": ":bed:",
            "code_decimal": "&#128719;",
            "category": "o",
            "emoji_order": "1669"
        },
        {
            "name": "couch_and_lamp",
            "unicode": "1f6cb",
            "shortname": ":couch:",
            "code_decimal": "&#128715;",
            "category": "o",
            "emoji_order": "1670"
        },
        {
            "name": "toilet",
            "unicode": "1f6bd",
            "shortname": ":toilet:",
            "code_decimal": "&#128701;",
            "category": "o",
            "emoji_order": "1671"
        },
        {
            "name": "shower",
            "unicode": "1f6bf",
            "shortname": ":shower:",
            "code_decimal": "&#128703;",
            "category": "o",
            "emoji_order": "1672"
        },
        {
            "name": "bath",
            "unicode": "1f6c0",
            "shortname": ":bath:",
            "code_decimal": "&#128704;",
            "category": "a",
            "emoji_order": "1673"
        },
        {
            "name": "bathtub",
            "unicode": "1f6c1",
            "shortname": ":bathtub:",
            "code_decimal": "&#128705;",
            "category": "o",
            "emoji_order": "1679"
        },
        {
            "name": "hourglass",
            "unicode": "231b",
            "shortname": ":hourglass:",
            "code_decimal": "&#8987;",
            "category": "o",
            "emoji_order": "1680"
        },
        {
            "name": "hourglass_flowing_sand",
            "unicode": "23f3",
            "shortname": ":hourglass_flowing_sand:",
            "code_decimal": "&#9203;",
            "category": "o",
            "emoji_order": "1681"
        },
        {
            "name": "watch",
            "unicode": "231a",
            "shortname": ":watch:",
            "code_decimal": "&#8986;",
            "category": "o",
            "emoji_order": "1682"
        },
        {
            "name": "alarm_clock",
            "unicode": "23f0",
            "shortname": ":alarm_clock:",
            "code_decimal": "&#9200;",
            "category": "o",
            "emoji_order": "1683"
        },
        {
            "name": "stopwatch",
            "unicode": "23f1",
            "shortname": ":stopwatch:",
            "code_decimal": "&#9201;",
            "category": "o",
            "emoji_order": "1684"
        },
        {
            "name": "timer_clock",
            "unicode": "23f2",
            "shortname": ":timer:",
            "code_decimal": "&#9202;",
            "category": "o",
            "emoji_order": "1685"
        },
        {
            "name": "mantelpiece_clock",
            "unicode": "1f570",
            "shortname": ":clock:",
            "code_decimal": "&#128368;",
            "category": "o",
            "emoji_order": "1686"
        },
        {
            "name": "clock12",
            "unicode": "1f55b",
            "shortname": ":clock12:",
            "code_decimal": "&#128347;",
            "category": "s",
            "emoji_order": "1687"
        },
        {
            "name": "clock1230",
            "unicode": "1f567",
            "shortname": ":clock1230:",
            "code_decimal": "&#128359;",
            "category": "s",
            "emoji_order": "1688"
        },
        {
            "name": "clock1",
            "unicode": "1f550",
            "shortname": ":clock1:",
            "code_decimal": "&#128336;",
            "category": "s",
            "emoji_order": "1689"
        },
        {
            "name": "clock130",
            "unicode": "1f55c",
            "shortname": ":clock130:",
            "code_decimal": "&#128348;",
            "category": "s",
            "emoji_order": "1690"
        },
        {
            "name": "clock2",
            "unicode": "1f551",
            "shortname": ":clock2:",
            "code_decimal": "&#128337;",
            "category": "s",
            "emoji_order": "1691"
        },
        {
            "name": "clock230",
            "unicode": "1f55d",
            "shortname": ":clock230:",
            "code_decimal": "&#128349;",
            "category": "s",
            "emoji_order": "1692"
        },
        {
            "name": "clock3",
            "unicode": "1f552",
            "shortname": ":clock3:",
            "code_decimal": "&#128338;",
            "category": "s",
            "emoji_order": "1693"
        },
        {
            "name": "clock330",
            "unicode": "1f55e",
            "shortname": ":clock330:",
            "code_decimal": "&#128350;",
            "category": "s",
            "emoji_order": "1694"
        },
        {
            "name": "clock4",
            "unicode": "1f553",
            "shortname": ":clock4:",
            "code_decimal": "&#128339;",
            "category": "s",
            "emoji_order": "1695"
        },
        {
            "name": "clock430",
            "unicode": "1f55f",
            "shortname": ":clock430:",
            "code_decimal": "&#128351;",
            "category": "s",
            "emoji_order": "1696"
        },
        {
            "name": "clock5",
            "unicode": "1f554",
            "shortname": ":clock5:",
            "code_decimal": "&#128340;",
            "category": "s",
            "emoji_order": "1697"
        },
        {
            "name": "clock530",
            "unicode": "1f560",
            "shortname": ":clock530:",
            "code_decimal": "&#128352;",
            "category": "s",
            "emoji_order": "1698"
        },
        {
            "name": "clock6",
            "unicode": "1f555",
            "shortname": ":clock6:",
            "code_decimal": "&#128341;",
            "category": "s",
            "emoji_order": "1699"
        },
        {
            "name": "clock630",
            "unicode": "1f561",
            "shortname": ":clock630:",
            "code_decimal": "&#128353;",
            "category": "s",
            "emoji_order": "1700"
        },
        {
            "name": "clock7",
            "unicode": "1f556",
            "shortname": ":clock7:",
            "code_decimal": "&#128342;",
            "category": "s",
            "emoji_order": "1701"
        },
        {
            "name": "clock730",
            "unicode": "1f562",
            "shortname": ":clock730:",
            "code_decimal": "&#128354;",
            "category": "s",
            "emoji_order": "1702"
        },
        {
            "name": "clock8",
            "unicode": "1f557",
            "shortname": ":clock8:",
            "code_decimal": "&#128343;",
            "category": "s",
            "emoji_order": "1703"
        },
        {
            "name": "clock830",
            "unicode": "1f563",
            "shortname": ":clock830:",
            "code_decimal": "&#128355;",
            "category": "s",
            "emoji_order": "1704"
        },
        {
            "name": "clock9",
            "unicode": "1f558",
            "shortname": ":clock9:",
            "code_decimal": "&#128344;",
            "category": "s",
            "emoji_order": "1705"
        },
        {
            "name": "clock930",
            "unicode": "1f564",
            "shortname": ":clock930:",
            "code_decimal": "&#128356;",
            "category": "s",
            "emoji_order": "1706"
        },
        {
            "name": "clock10",
            "unicode": "1f559",
            "shortname": ":clock10:",
            "code_decimal": "&#128345;",
            "category": "s",
            "emoji_order": "1707"
        },
        {
            "name": "clock1030",
            "unicode": "1f565",
            "shortname": ":clock1030:",
            "code_decimal": "&#128357;",
            "category": "s",
            "emoji_order": "1708"
        },
        {
            "name": "clock11",
            "unicode": "1f55a",
            "shortname": ":clock11:",
            "code_decimal": "&#128346;",
            "category": "s",
            "emoji_order": "1709"
        },
        {
            "name": "clock1130",
            "unicode": "1f566",
            "shortname": ":clock1130:",
            "code_decimal": "&#128358;",
            "category": "s",
            "emoji_order": "1710"
        },
        {
            "name": "new_moon",
            "unicode": "1f311",
            "shortname": ":new_moon:",
            "code_decimal": "&#127761;",
            "category": "n",
            "emoji_order": "1711"
        },
        {
            "name": "waxing_crescent_moon",
            "unicode": "1f312",
            "shortname": ":waxing_crescent_moon:",
            "code_decimal": "&#127762;",
            "category": "n",
            "emoji_order": "1712"
        },
        {
            "name": "first_quarter_moon",
            "unicode": "1f313",
            "shortname": ":first_quarter_moon:",
            "code_decimal": "&#127763;",
            "category": "n",
            "emoji_order": "1713"
        },
        /*{ //@todo not found on image
    "name": "waxing_gibbous_moon",
    "unicode": "1f314",
    "shortname": ":waxing_gibbous_moon:",
    "code_decimal": "&#127764;",
    "category": "n",
    "emoji_order": "1714"
  },*/
        {
            "name": "full_moon",
            "unicode": "1f315",
            "shortname": ":full_moon:",
            "code_decimal": "&#127765;",
            "category": "n",
            "emoji_order": "1715"
        },
        {
            "name": "waning_gibbous_moon",
            "unicode": "1f316",
            "shortname": ":waning_gibbous_moon:",
            "code_decimal": "&#127766;",
            "category": "n",
            "emoji_order": "1716"
        },
        {
            "name": "last_quarter_moon",
            "unicode": "1f317",
            "shortname": ":last_quarter_moon:",
            "code_decimal": "&#127767;",
            "category": "n",
            "emoji_order": "1717"
        },
        {
            "name": "waning_crescent_moon",
            "unicode": "1f318",
            "shortname": ":waning_crescent_moon:",
            "code_decimal": "&#127768;",
            "category": "n",
            "emoji_order": "1718"
        },
        {
            "name": "crescent_moon",
            "unicode": "1f319",
            "shortname": ":crescent_moon:",
            "code_decimal": "&#127769;",
            "category": "n",
            "emoji_order": "1719"
        },
        {
            "name": "new_moon_with_face",
            "unicode": "1f31a",
            "shortname": ":new_moon_with_face:",
            "code_decimal": "&#127770;",
            "category": "n",
            "emoji_order": "1720"
        },
        {
            "name": "first_quarter_moon_with_face",
            "unicode": "1f31b",
            "shortname": ":first_quarter_moon_with_face:",
            "code_decimal": "&#127771;",
            "category": "n",
            "emoji_order": "1721"
        },
        {
            "name": "last_quarter_moon_with_face",
            "unicode": "1f31c",
            "shortname": ":last_quarter_moon_with_face:",
            "code_decimal": "&#127772;",
            "category": "n",
            "emoji_order": "1722"
        },
        {
            "name": "thermometer",
            "unicode": "1f321",
            "shortname": ":thermometer:",
            "code_decimal": "&#127777;",
            "category": "o",
            "emoji_order": "1723"
        },
        {
            "name": "sunny",
            "unicode": "2600",
            "shortname": ":sunny:",
            "code_decimal": "&#9728;",
            "category": "n",
            "emoji_order": "1724"
        },
        {
            "name": "full_moon_with_face",
            "unicode": "1f31d",
            "shortname": ":full_moon_with_face:",
            "code_decimal": "&#127773;",
            "category": "n",
            "emoji_order": "1725"
        },
        {
            "name": "sun_with_face",
            "unicode": "1f31e",
            "shortname": ":sun_with_face:",
            "code_decimal": "&#127774;",
            "category": "n",
            "emoji_order": "1726"
        },
        {
            "name": "star",
            "unicode": "2b50",
            "shortname": ":star:",
            "code_decimal": "&#11088;",
            "category": "n",
            "emoji_order": "1727"
        },
        {
            "name": "star2",
            "unicode": "1f31f",
            "shortname": ":star2:",
            "code_decimal": "&#127775;",
            "category": "n",
            "emoji_order": "1728"
        },
        {
            "name": "stars",
            "unicode": "1f320",
            "shortname": ":stars:",
            "code_decimal": "&#127776;",
            "category": "t",
            "emoji_order": "1729"
        },
        {
            "name": "cloud",
            "unicode": "2601",
            "shortname": ":cloud:",
            "code_decimal": "&#9729;",
            "category": "n",
            "emoji_order": "1730"
        },
        {
            "name": "partly_sunny",
            "unicode": "26c5",
            "shortname": ":partly_sunny:",
            "code_decimal": "&#9925;",
            "category": "n",
            "emoji_order": "1731"
        },
        {
            "name": "thunder_cloud_and_rain",
            "unicode": "26c8",
            "shortname": ":thunder_cloud_rain:",
            "code_decimal": "&#9928;",
            "category": "n",
            "emoji_order": "1732"
        },
        /*{ //@todo not found on image
    "name": "white_sun_small_cloud",
    "unicode": "1f324",
    "shortname": ":white_sun_small_cloud:",
    "code_decimal": "&#127780;",
    "category": "n",
    "emoji_order": "1733"
  },*/
        /*{ //@todo not found on image
    "name": "white_sun_cloud",
    "unicode": "1f325",
    "shortname": ":white_sun_cloud:",
    "code_decimal": "&#127781;",
    "category": "n",
    "emoji_order": "1734"
  },*/
        /*{ //@todo not found on image
    "name": "white_sun_rain_cloud",
    "unicode": "1f326",
    "shortname": ":white_sun_rain_cloud:",
    "code_decimal": "&#127782;",
    "category": "n",
    "emoji_order": "1735"
  },*/
        {
            "name": "rain_cloud",
            "unicode": "1f327",
            "shortname": ":cloud_rain:",
            "code_decimal": "&#127783;",
            "category": "n",
            "emoji_order": "1736"
        },
        {
            "name": "snow_cloud",
            "unicode": "1f328",
            "shortname": ":cloud_snow:",
            "code_decimal": "&#127784;",
            "category": "n",
            "emoji_order": "1737"
        },
        /*{ //@todo not found on image
    "name": "cloud_lightning",
    "unicode": "1f329",
    "shortname": ":cloud_lightning:",
    "code_decimal": "&#127785;",
    "category": "n",
    "emoji_order": "1738"
  },*/
        /*{ //@todo not found on image
    "name": "cloud_tornado",
    "unicode": "1f32a",
    "shortname": ":cloud_tornado:",
    "code_decimal": "&#127786;",
    "category": "n",
    "emoji_order": "1739"
  },*/
        {
            "name": "fog",
            "unicode": "1f32b",
            "shortname": ":fog:",
            "code_decimal": "&#127787;",
            "category": "n",
            "emoji_order": "1740"
        },
        {
            "name": "wind_blowing_face",
            "unicode": "1f32c",
            "shortname": ":wind_blowing_face:",
            "code_decimal": "&#127788;",
            "category": "n",
            "emoji_order": "1741"
        },
        {
            "name": "cyclone",
            "unicode": "1f300",
            "shortname": ":cyclone:",
            "code_decimal": "&#127744;",
            "category": "s",
            "emoji_order": "1742"
        },
        {
            "name": "rainbow",
            "unicode": "1f308",
            "shortname": ":rainbow:",
            "code_decimal": "&#127752;",
            "category": "t",
            "emoji_order": "1743"
        },
        {
            "name": "closed_umbrella",
            "unicode": "1f302",
            "shortname": ":closed_umbrella:",
            "code_decimal": "&#127746;",
            "category": "p",
            "emoji_order": "1744"
        },
        {
            "name": "umbrella",
            "unicode": "2602",
            "shortname": ":umbrella2:",
            "code_decimal": "&#9730;",
            "category": "n",
            "emoji_order": "1745"
        },
        {
            "name": "umbrella_with_rain_drops",
            "unicode": "2614",
            "shortname": ":umbrella:",
            "code_decimal": "&#9748;",
            "category": "n",
            "emoji_order": "1746"
        },
        {
            "name": "beach_umbrella",
            "unicode": "26f1",
            "shortname": ":beach_umbrella:",
            "code_decimal": "&#9969;",
            "category": "o",
            "emoji_order": "1747"
        },
        {
            "name": "zap",
            "unicode": "26a1",
            "shortname": ":zap:",
            "code_decimal": "&#9889;",
            "category": "n",
            "emoji_order": "1748"
        },
        {
            "name": "snowflake",
            "unicode": "2744",
            "shortname": ":snowflake:",
            "code_decimal": "&#10052;",
            "category": "n",
            "emoji_order": "1749"
        },
        {
            "name": "snowman",
            "unicode": "2603",
            "shortname": ":snowman2:",
            "code_decimal": "&#9731;",
            "category": "n",
            "emoji_order": "1750"
        },
        {
            "name": "snowman_without_snow",
            "unicode": "26c4",
            "shortname": ":snowman:",
            "code_decimal": "&#9924;",
            "category": "n",
            "emoji_order": "1751"
        },
        {
            "name": "comet",
            "unicode": "2604",
            "shortname": ":comet:",
            "code_decimal": "&#9732;",
            "category": "n",
            "emoji_order": "1752"
        },
        {
            "name": "fire",
            "unicode": "1f525",
            "shortname": ":fire:",
            "code_decimal": "&#128293;",
            "category": "n",
            "emoji_order": "1753"
        },
        {
            "name": "droplet",
            "unicode": "1f4a7",
            "shortname": ":droplet:",
            "code_decimal": "&#128167;",
            "category": "n",
            "emoji_order": "1754"
        },
        {
            "name": "ocean",
            "unicode": "1f30a",
            "shortname": ":ocean:",
            "code_decimal": "&#127754;",
            "category": "n",
            "emoji_order": "1755"
        },
        {
            "name": "jack_o_lantern",
            "unicode": "1f383",
            "shortname": ":jack_o_lantern:",
            "code_decimal": "&#127875;",
            "category": "n",
            "emoji_order": "1756"
        },
        {
            "name": "christmas_tree",
            "unicode": "1f384",
            "shortname": ":christmas_tree:",
            "code_decimal": "&#127876;",
            "category": "n",
            "emoji_order": "1757"
        },
        {
            "name": "fireworks",
            "unicode": "1f386",
            "shortname": ":fireworks:",
            "code_decimal": "&#127878;",
            "category": "t",
            "emoji_order": "1758"
        },
        {
            "name": "sparkler",
            "unicode": "1f387",
            "shortname": ":sparkler:",
            "code_decimal": "&#127879;",
            "category": "t",
            "emoji_order": "1759"
        },
        {
            "name": "sparkles",
            "unicode": "2728",
            "shortname": ":sparkles:",
            "code_decimal": "&#10024;",
            "category": "n",
            "emoji_order": "1760"
        },
        {
            "name": "balloon",
            "unicode": "1f388",
            "shortname": ":balloon:",
            "code_decimal": "&#127880;",
            "category": "o",
            "emoji_order": "1761"
        },
        {
            "name": "tada",
            "unicode": "1f389",
            "shortname": ":tada:",
            "code_decimal": "&#127881;",
            "category": "o",
            "emoji_order": "1762"
        },
        {
            "name": "confetti_ball",
            "unicode": "1f38a",
            "shortname": ":confetti_ball:",
            "code_decimal": "&#127882;",
            "category": "o",
            "emoji_order": "1763"
        },
        {
            "name": "tanabata_tree",
            "unicode": "1f38b",
            "shortname": ":tanabata_tree:",
            "code_decimal": "&#127883;",
            "category": "n",
            "emoji_order": "1764"
        },
        {
            "name": "bamboo",
            "unicode": "1f38d",
            "shortname": ":bamboo:",
            "code_decimal": "&#127885;",
            "category": "n",
            "emoji_order": "1765"
        },
        {
            "name": "dolls",
            "unicode": "1f38e",
            "shortname": ":dolls:",
            "code_decimal": "&#127886;",
            "category": "o",
            "emoji_order": "1766"
        },
        {
            "name": "f",
            "unicode": "1f38f",
            "shortname": ":flags:",
            "code_decimal": "&#127887;",
            "category": "o",
            "emoji_order": "1767"
        },
        {
            "name": "wind_chime",
            "unicode": "1f390",
            "shortname": ":wind_chime:",
            "code_decimal": "&#127888;",
            "category": "o",
            "emoji_order": "1768"
        },
        {
            "name": "rice_scene",
            "unicode": "1f391",
            "shortname": ":rice_scene:",
            "code_decimal": "&#127889;",
            "category": "t",
            "emoji_order": "1769"
        },
        {
            "name": "ribbon",
            "unicode": "1f380",
            "shortname": ":ribbon:",
            "code_decimal": "&#127872;",
            "category": "o",
            "emoji_order": "1770"
        },
        {
            "name": "gift",
            "unicode": "1f381",
            "shortname": ":gift:",
            "code_decimal": "&#127873;",
            "category": "o",
            "emoji_order": "1771"
        },
        {
            "name": "reminder_ribbon",
            "unicode": "1f397",
            "shortname": ":reminder_ribbon:",
            "code_decimal": "&#127895;",
            "category": "a",
            "emoji_order": "1772"
        },
        {
            "name": "admission_tickets",
            "unicode": "1f39f",
            "shortname": ":tickets:",
            "code_decimal": "&#127903;",
            "category": "a",
            "emoji_order": "1773"
        },
        {
            "name": "ticket",
            "unicode": "1f3ab",
            "shortname": ":ticket:",
            "code_decimal": "&#127915;",
            "category": "a",
            "emoji_order": "1774"
        },
        {
            "name": "medal",
            "unicode": "1f396",
            "shortname": ":military_medal:",
            "code_decimal": "&#127894;",
            "category": "a",
            "emoji_order": "1775"
        },
        {
            "name": "trophy",
            "unicode": "1f3c6",
            "shortname": ":trophy:",
            "code_decimal": "&#127942;",
            "category": "a",
            "emoji_order": "1776"
        },
        {
            "name": "sports_medal",
            "unicode": "1f3c5",
            "shortname": ":medal:",
            "code_decimal": "&#127941;",
            "category": "a",
            "emoji_order": "1777"
        },
        /*{ //@todo not found on image
    "name": "first_place",
    "unicode": "1f947",
    "shortname": ":first_place:",
    "code_decimal": "&#129351;",
    "category": "a",
    "emoji_order": "1778"
  },*/
        /*{ //@todo not found on image
    "name": "second_place",
    "unicode": "1f948",
    "shortname": ":second_place:",
    "code_decimal": "&#129352;",
    "category": "a",
    "emoji_order": "1779"
  },*/
        /*{ //@todo not found on image
    "name": "third_place",
    "unicode": "1f949",
    "shortname": ":third_place:",
    "code_decimal": "&#129353;",
    "category": "a",
    "emoji_order": "1780"
  },*/
        {
            "name": "soccer",
            "unicode": "26bd",
            "shortname": ":soccer:",
            "code_decimal": "&#9917;",
            "category": "a",
            "emoji_order": "1781"
        },
        {
            "name": "baseball",
            "unicode": "26be",
            "shortname": ":baseball:",
            "code_decimal": "&#9918;",
            "category": "a",
            "emoji_order": "1782"
        },
        {
            "name": "basketball",
            "unicode": "1f3c0",
            "shortname": ":basketball:",
            "code_decimal": "&#127936;",
            "category": "a",
            "emoji_order": "1783"
        },
        {
            "name": "volleyball",
            "unicode": "1f3d0",
            "shortname": ":volleyball:",
            "code_decimal": "&#127952;",
            "category": "a",
            "emoji_order": "1784"
        },
        {
            "name": "football",
            "unicode": "1f3c8",
            "shortname": ":football:",
            "code_decimal": "&#127944;",
            "category": "a",
            "emoji_order": "1785"
        },
        {
            "name": "rugby_football",
            "unicode": "1f3c9",
            "shortname": ":rugby_football:",
            "code_decimal": "&#127945;",
            "category": "a",
            "emoji_order": "1786"
        },
        {
            "name": "tennis",
            "unicode": "1f3be",
            "shortname": ":tennis:",
            "code_decimal": "&#127934;",
            "category": "a",
            "emoji_order": "1787"
        },
        {
            "name": "8ball",
            "unicode": "1f3b1",
            "shortname": ":8ball:",
            "code_decimal": "&#127921;",
            "category": "a",
            "emoji_order": "1788"
        },
        {
            "name": "bowling",
            "unicode": "1f3b3",
            "shortname": ":bowling:",
            "code_decimal": "&#127923;",
            "category": "a",
            "emoji_order": "1789"
        },
        {
            "name": "cricket_bat_and_ball",
            "unicode": "1f3cf",
            "shortname": ":cricket_game:",
            "code_decimal": "&#127951;",
            "category": "a",
            "emoji_order": "1790"
        },
        {
            "name": "field_hockey_stick_and_ball",
            "unicode": "1f3d1",
            "shortname": ":field_hockey:",
            "code_decimal": "&#127953;",
            "category": "a",
            "emoji_order": "1791"
        },
        {
            "name": "ice_hockey_stick_and_puck",
            "unicode": "1f3d2",
            "shortname": ":hockey:",
            "code_decimal": "&#127954;",
            "category": "a",
            "emoji_order": "1792"
        },
        {
            "name": "table_tennis_paddle_and_ball",
            "unicode": "1f3d3",
            "shortname": ":ping_pong:",
            "code_decimal": "&#127955;",
            "category": "a",
            "emoji_order": "1793"
        },
        {
            "name": "badminton_racquet_and_shuttlecock",
            "unicode": "1f3f8",
            "shortname": ":badminton:",
            "code_decimal": "&#127992;",
            "category": "a",
            "emoji_order": "1794"
        },
        /*{ //@todo not found on image
    "name": "boxing_glove",
    "unicode": "1f94a",
    "shortname": ":boxing_glove:",
    "code_decimal": "&#129354;",
    "category": "a",
    "emoji_order": "1795"
  },*/
        /*{ //@todo not found on image
    "name": "martial_arts_uniform",
    "unicode": "1f94b",
    "shortname": ":martial_arts_uniform:",
    "code_decimal": "&#129355;",
    "category": "a",
    "emoji_order": "1796"
  },*/
        /*{ @todo not found on image
    "name": "goal",
    "unicode": "1f945",
    "shortname": ":goal:",
    "code_decimal": "&#129349;",
    "category": "a",
    "emoji_order": "1797"
  },*/
        {
            "name": "dart",
            "unicode": "1f3af",
            "shortname": ":dart:",
            "code_decimal": "&#127919;",
            "category": "a",
            "emoji_order": "1798"
        },
        {
            "name": "golf",
            "unicode": "26f3",
            "shortname": ":golf:",
            "code_decimal": "&#9971;",
            "category": "a",
            "emoji_order": "1799"
        },
        {
            "name": "ice_skate",
            "unicode": "26f8",
            "shortname": ":ice_skate:",
            "code_decimal": "&#9976;",
            "category": "a",
            "emoji_order": "1800"
        },
        {
            "name": "fishing_pole_and_fish",
            "unicode": "1f3a3",
            "shortname": ":fishing_pole_and_fish:",
            "code_decimal": "&#127907;",
            "category": "a",
            "emoji_order": "1801"
        },
        {
            "name": "running_shirt_with_sash",
            "unicode": "1f3bd",
            "shortname": ":running_shirt_with_sash:",
            "code_decimal": "&#127933;",
            "category": "a",
            "emoji_order": "1802"
        },
        {
            "name": "ski",
            "unicode": "1f3bf",
            "shortname": ":ski:",
            "code_decimal": "&#127935;",
            "category": "a",
            "emoji_order": "1803"
        },
        {
            "name": "video_game",
            "unicode": "1f3ae",
            "shortname": ":video_game:",
            "code_decimal": "&#127918;",
            "category": "a",
            "emoji_order": "1804"
        },
        {
            "name": "joystick",
            "unicode": "1f579",
            "shortname": ":joystick:",
            "code_decimal": "&#128377;",
            "category": "o",
            "emoji_order": "1805"
        },
        {
            "name": "game_die",
            "unicode": "1f3b2",
            "shortname": ":game_die:",
            "code_decimal": "&#127922;",
            "category": "a",
            "emoji_order": "1806"
        },
        {
            "name": "spades",
            "unicode": "2660",
            "shortname": ":spades:",
            "code_decimal": "&spades;",
            "category": "s",
            "emoji_order": "1807"
        },
        {
            "name": "hearts",
            "unicode": "2665",
            "shortname": ":hearts:",
            "code_decimal": "&hearts;",
            "category": "s",
            "emoji_order": "1808"
        },
        {
            "name": "diamonds",
            "unicode": "2666",
            "shortname": ":diamonds:",
            "code_decimal": "&diams;",
            "category": "s",
            "emoji_order": "1809"
        },
        {
            "name": "clubs",
            "unicode": "2663",
            "shortname": ":clubs:",
            "code_decimal": "&clubs;",
            "category": "s",
            "emoji_order": "1810"
        },
        {
            "name": "black_joker",
            "unicode": "1f0cf",
            "shortname": ":black_joker:",
            "code_decimal": "&#127183;",
            "category": "s",
            "emoji_order": "1811"
        },
        {
            "name": "mahjong",
            "unicode": "1f004",
            "shortname": ":mahjong:",
            "code_decimal": "&#126980;",
            "category": "s",
            "emoji_order": "1812"
        },
        {
            "name": "flower_playing_cards",
            "unicode": "1f3b4",
            "shortname": ":flower_playing_cards:",
            "code_decimal": "&#127924;",
            "category": "s",
            "emoji_order": "1813"
        },
        {
            "name": "mute",
            "unicode": "1f507",
            "shortname": ":mute:",
            "code_decimal": "&#128263;",
            "category": "s",
            "emoji_order": "1814"
        },
        {
            "name": "speaker",
            "unicode": "1f508",
            "shortname": ":speaker:",
            "code_decimal": "&#128264;",
            "category": "s",
            "emoji_order": "1815"
        },
        {
            "name": "sound",
            "unicode": "1f509",
            "shortname": ":sound:",
            "code_decimal": "&#128265;",
            "category": "s",
            "emoji_order": "1816"
        },
        {
            "name": "loud_sound",
            "unicode": "1f50a",
            "shortname": ":loud_sound:",
            "code_decimal": "&#128266;",
            "category": "s",
            "emoji_order": "1817"
        },
        {
            "name": "loudspeaker",
            "unicode": "1f4e2",
            "shortname": ":loudspeaker:",
            "code_decimal": "&#128226;",
            "category": "s",
            "emoji_order": "1818"
        },
        {
            "name": "mega",
            "unicode": "1f4e3",
            "shortname": ":mega:",
            "code_decimal": "&#128227;",
            "category": "s",
            "emoji_order": "1819"
        },
        {
            "name": "postal_horn",
            "unicode": "1f4ef",
            "shortname": ":postal_horn:",
            "code_decimal": "&#128239;",
            "category": "o",
            "emoji_order": "1820"
        },
        {
            "name": "bell",
            "unicode": "1f514",
            "shortname": ":bell:",
            "code_decimal": "&#128276;",
            "category": "s",
            "emoji_order": "1821"
        },
        {
            "name": "no_bell",
            "unicode": "1f515",
            "shortname": ":no_bell:",
            "code_decimal": "&#128277;",
            "category": "s",
            "emoji_order": "1822"
        },
        {
            "name": "musical_score",
            "unicode": "1f3bc",
            "shortname": ":musical_score:",
            "code_decimal": "&#127932;",
            "category": "a",
            "emoji_order": "1823"
        },
        {
            "name": "musical_note",
            "unicode": "1f3b5",
            "shortname": ":musical_note:",
            "code_decimal": "&#127925;",
            "category": "s",
            "emoji_order": "1824"
        },
        {
            "name": "notes",
            "unicode": "1f3b6",
            "shortname": ":notes:",
            "code_decimal": "&#127926;",
            "category": "s",
            "emoji_order": "1825"
        },
        {
            "name": "studio_microphone",
            "unicode": "1f399",
            "shortname": ":microphone2:",
            "code_decimal": "&#127897;",
            "category": "o",
            "emoji_order": "1826"
        },
        {
            "name": "level_slider",
            "unicode": "1f39a",
            "shortname": ":level_slider:",
            "code_decimal": "&#127898;",
            "category": "o",
            "emoji_order": "1827"
        },
        {
            "name": "control_knobs",
            "unicode": "1f39b",
            "shortname": ":control_knobs:",
            "code_decimal": "&#127899;",
            "category": "o",
            "emoji_order": "1828"
        },
        {
            "name": "microphone",
            "unicode": "1f3a4",
            "shortname": ":microphone:",
            "code_decimal": "&#127908;",
            "category": "a",
            "emoji_order": "1829"
        },
        {
            "name": "headphones",
            "unicode": "1f3a7",
            "shortname": ":headphones:",
            "code_decimal": "&#127911;",
            "category": "a",
            "emoji_order": "1830"
        },
        {
            "name": "radio",
            "unicode": "1f4fb",
            "shortname": ":radio:",
            "code_decimal": "&#128251;",
            "category": "o",
            "emoji_order": "1831"
        },
        {
            "name": "saxophone",
            "unicode": "1f3b7",
            "shortname": ":saxophone:",
            "code_decimal": "&#127927;",
            "category": "a",
            "emoji_order": "1832"
        },
        {
            "name": "guitar",
            "unicode": "1f3b8",
            "shortname": ":guitar:",
            "code_decimal": "&#127928;",
            "category": "a",
            "emoji_order": "1833"
        },
        {
            "name": "musical_keyboard",
            "unicode": "1f3b9",
            "shortname": ":musical_keyboard:",
            "code_decimal": "&#127929;",
            "category": "a",
            "emoji_order": "1834"
        },
        {
            "name": "trumpet",
            "unicode": "1f3ba",
            "shortname": ":trumpet:",
            "code_decimal": "&#127930;",
            "category": "a",
            "emoji_order": "1835"
        },
        {
            "name": "violin",
            "unicode": "1f3bb",
            "shortname": ":violin:",
            "code_decimal": "&#127931;",
            "category": "a",
            "emoji_order": "1836"
        },
        /*{ //@todo not found on image
    "name": "drum",
    "unicode": "1f941",
    "shortname": ":drum:",
    "code_decimal": "&#129345;",
    "category": "a",
    "emoji_order": "1837"
  },*/
        {
            "name": "iphone",
            "unicode": "1f4f1",
            "shortname": ":iphone:",
            "code_decimal": "&#128241;",
            "category": "o",
            "emoji_order": "1838"
        },
        {
            "name": "calling",
            "unicode": "1f4f2",
            "shortname": ":calling:",
            "code_decimal": "&#128242;",
            "category": "o",
            "emoji_order": "1839"
        },
        {
            "name": "telephone",
            "unicode": "260e",
            "shortname": ":telephone:",
            "code_decimal": "&#9742;",
            "category": "o",
            "emoji_order": "1840"
        },
        {
            "name": "telephone_receiver",
            "unicode": "1f4de",
            "shortname": ":telephone_receiver:",
            "code_decimal": "&#128222;",
            "category": "o",
            "emoji_order": "1841"
        },
        {
            "name": "pager",
            "unicode": "1f4df",
            "shortname": ":pager:",
            "code_decimal": "&#128223;",
            "category": "o",
            "emoji_order": "1842"
        },
        {
            "name": "fax",
            "unicode": "1f4e0",
            "shortname": ":fax:",
            "code_decimal": "&#128224;",
            "category": "o",
            "emoji_order": "1843"
        },
        {
            "name": "battery",
            "unicode": "1f50b",
            "shortname": ":battery:",
            "code_decimal": "&#128267;",
            "category": "o",
            "emoji_order": "1844"
        },
        {
            "name": "electric_plug",
            "unicode": "1f50c",
            "shortname": ":electric_plug:",
            "code_decimal": "&#128268;",
            "category": "o",
            "emoji_order": "1845"
        },
        {
            "name": "computer",
            "unicode": "1f4bb",
            "shortname": ":computer:",
            "code_decimal": "&#128187;",
            "category": "o",
            "emoji_order": "1846"
        },
        {
            "name": "desktop_computer",
            "unicode": "1f5a5",
            "shortname": ":desktop:",
            "code_decimal": "&#128421;",
            "category": "o",
            "emoji_order": "1847"
        },
        {
            "name": "printer",
            "unicode": "1f5a8",
            "shortname": ":printer:",
            "code_decimal": "&#128424;",
            "category": "o",
            "emoji_order": "1848"
        },
        {
            "name": "keyboard",
            "unicode": "2328",
            "shortname": ":keyboard:",
            "code_decimal": "&#9000;",
            "category": "o",
            "emoji_order": "1849"
        },
        {
            "name": "three_button_mouse",
            "unicode": "1f5b1",
            "shortname": ":mouse_three_button:",
            "code_decimal": "&#128433;",
            "category": "o",
            "emoji_order": "1850"
        },
        {
            "name": "trackball",
            "unicode": "1f5b2",
            "shortname": ":trackball:",
            "code_decimal": "&#128434;",
            "category": "o",
            "emoji_order": "1851"
        },
        {
            "name": "minidisc",
            "unicode": "1f4bd",
            "shortname": ":minidisc:",
            "code_decimal": "&#128189;",
            "category": "o",
            "emoji_order": "1852"
        },
        {
            "name": "floppy_disk",
            "unicode": "1f4be",
            "shortname": ":floppy_disk:",
            "code_decimal": "&#128190;",
            "category": "o",
            "emoji_order": "1853"
        },
        {
            "name": "cd",
            "unicode": "1f4bf",
            "shortname": ":cd:",
            "code_decimal": "&#128191;",
            "category": "o",
            "emoji_order": "1854"
        },
        {
            "name": "dvd",
            "unicode": "1f4c0",
            "shortname": ":dvd:",
            "code_decimal": "&#128192;",
            "category": "o",
            "emoji_order": "1855"
        },
        {
            "name": "movie_camera",
            "unicode": "1f3a5",
            "shortname": ":movie_camera:",
            "code_decimal": "&#127909;",
            "category": "o",
            "emoji_order": "1856"
        },
        {
            "name": "film_frames",
            "unicode": "1f39e",
            "shortname": ":film_frames:",
            "code_decimal": "&#127902;",
            "category": "o",
            "emoji_order": "1857"
        },
        {
            "name": "film_projector",
            "unicode": "1f4fd",
            "shortname": ":projector:",
            "code_decimal": "&#128253;",
            "category": "o",
            "emoji_order": "1858"
        },
        {
            "name": "clapper",
            "unicode": "1f3ac",
            "shortname": ":clapper:",
            "code_decimal": "&#127916;",
            "category": "a",
            "emoji_order": "1859"
        },
        {
            "name": "tv",
            "unicode": "1f4fa",
            "shortname": ":tv:",
            "code_decimal": "&#128250;",
            "category": "o",
            "emoji_order": "1860"
        },
        {
            "name": "camera",
            "unicode": "1f4f7",
            "shortname": ":camera:",
            "code_decimal": "&#128247;",
            "category": "o",
            "emoji_order": "1861"
        },
        {
            "name": "camera_with_flash",
            "unicode": "1f4f8",
            "shortname": ":camera_with_flash:",
            "code_decimal": "&#128248;",
            "category": "o",
            "emoji_order": "1862"
        },
        {
            "name": "video_camera",
            "unicode": "1f4f9",
            "shortname": ":video_camera:",
            "code_decimal": "&#128249;",
            "category": "o",
            "emoji_order": "1863"
        },
        {
            "name": "vhs",
            "unicode": "1f4fc",
            "shortname": ":vhs:",
            "code_decimal": "&#128252;",
            "category": "o",
            "emoji_order": "1864"
        },
        {
            "name": "mag",
            "unicode": "1f50d",
            "shortname": ":mag:",
            "code_decimal": "&#128269;",
            "category": "o",
            "emoji_order": "1865"
        },
        {
            "name": "mag_right",
            "unicode": "1f50e",
            "shortname": ":mag_right:",
            "code_decimal": "&#128270;",
            "category": "o",
            "emoji_order": "1866"
        },
        {
            "name": "microscope",
            "unicode": "1f52c",
            "shortname": ":microscope:",
            "code_decimal": "&#128300;",
            "category": "o",
            "emoji_order": "1867"
        },
        {
            "name": "telescope",
            "unicode": "1f52d",
            "shortname": ":telescope:",
            "code_decimal": "&#128301;",
            "category": "o",
            "emoji_order": "1868"
        },
        {
            "name": "satellite_antenna",
            "unicode": "1f4e1",
            "shortname": ":satellite:",
            "code_decimal": "&#128225;",
            "category": "o",
            "emoji_order": "1869"
        },
        {
            "name": "candle",
            "unicode": "1f56f",
            "shortname": ":candle:",
            "code_decimal": "&#128367;",
            "category": "o",
            "emoji_order": "1870"
        },
        {
            "name": "bulb",
            "unicode": "1f4a1",
            "shortname": ":bulb:",
            "code_decimal": "&#128161;",
            "category": "o",
            "emoji_order": "1871"
        },
        {
            "name": "flashlight",
            "unicode": "1f526",
            "shortname": ":flashlight:",
            "code_decimal": "&#128294;",
            "category": "o",
            "emoji_order": "1872"
        },
        {
            "name": "izakaya_lantern",
            "unicode": "1f3ee",
            "shortname": ":izakaya_lantern:",
            "code_decimal": "&#127982;",
            "category": "o",
            "emoji_order": "1873"
        },
        {
            "name": "notebook_with_decorative_cover",
            "unicode": "1f4d4",
            "shortname": ":notebook_with_decorative_cover:",
            "code_decimal": "&#128212;",
            "category": "o",
            "emoji_order": "1874"
        },
        {
            "name": "closed_book",
            "unicode": "1f4d5",
            "shortname": ":closed_book:",
            "code_decimal": "&#128213;",
            "category": "o",
            "emoji_order": "1875"
        },
        {
            "name": "book",
            "unicode": "1f4d6",
            "shortname": ":book:",
            "code_decimal": "&#128214;",
            "category": "o",
            "emoji_order": "1876"
        },
        {
            "name": "green_book",
            "unicode": "1f4d7",
            "shortname": ":green_book:",
            "code_decimal": "&#128215;",
            "category": "o",
            "emoji_order": "1877"
        },
        {
            "name": "blue_book",
            "unicode": "1f4d8",
            "shortname": ":blue_book:",
            "code_decimal": "&#128216;",
            "category": "o",
            "emoji_order": "1878"
        },
        {
            "name": "orange_book",
            "unicode": "1f4d9",
            "shortname": ":orange_book:",
            "code_decimal": "&#128217;",
            "category": "o",
            "emoji_order": "1879"
        },
        {
            "name": "books",
            "unicode": "1f4da",
            "shortname": ":books:",
            "code_decimal": "&#128218;",
            "category": "o",
            "emoji_order": "1880"
        },
        {
            "name": "notebook",
            "unicode": "1f4d3",
            "shortname": ":notebook:",
            "code_decimal": "&#128211;",
            "category": "o",
            "emoji_order": "1881"
        },
        {
            "name": "ledger",
            "unicode": "1f4d2",
            "shortname": ":ledger:",
            "code_decimal": "&#128210;",
            "category": "o",
            "emoji_order": "1882"
        },
        {
            "name": "page_with_curl",
            "unicode": "1f4c3",
            "shortname": ":page_with_curl:",
            "code_decimal": "&#128195;",
            "category": "o",
            "emoji_order": "1883"
        },
        {
            "name": "scroll",
            "unicode": "1f4dc",
            "shortname": ":scroll:",
            "code_decimal": "&#128220;",
            "category": "o",
            "emoji_order": "1884"
        },
        {
            "name": "page_facing_up",
            "unicode": "1f4c4",
            "shortname": ":page_facing_up:",
            "code_decimal": "&#128196;",
            "category": "o",
            "emoji_order": "1885"
        },
        {
            "name": "newspaper",
            "unicode": "1f4f0",
            "shortname": ":newspaper:",
            "code_decimal": "&#128240;",
            "category": "o",
            "emoji_order": "1886"
        },
        {
            "name": "rolled_up_newspaper",
            "unicode": "1f5de",
            "shortname": ":newspaper2:",
            "code_decimal": "&#128478;",
            "category": "o",
            "emoji_order": "1887"
        },
        {
            "name": "bookmark_tabs",
            "unicode": "1f4d1",
            "shortname": ":bookmark_tabs:",
            "code_decimal": "&#128209;",
            "category": "o",
            "emoji_order": "1888"
        },
        {
            "name": "bookmark",
            "unicode": "1f516",
            "shortname": ":bookmark:",
            "code_decimal": "&#128278;",
            "category": "o",
            "emoji_order": "1889"
        },
        {
            "name": "label",
            "unicode": "1f3f7",
            "shortname": ":label:",
            "code_decimal": "&#127991;",
            "category": "o",
            "emoji_order": "1890"
        },
        {
            "name": "moneybag",
            "unicode": "1f4b0",
            "shortname": ":moneybag:",
            "code_decimal": "&#128176;",
            "category": "o",
            "emoji_order": "1891"
        },
        {
            "name": "yen",
            "unicode": "1f4b4",
            "shortname": ":yen:",
            "code_decimal": "&#128180;",
            "category": "o",
            "emoji_order": "1892"
        },
        {
            "name": "dollar",
            "unicode": "1f4b5",
            "shortname": ":dollar:",
            "code_decimal": "&#128181;",
            "category": "o",
            "emoji_order": "1893"
        },
        {
            "name": "euro",
            "unicode": "1f4b6",
            "shortname": ":euro:",
            "code_decimal": "&#128182;",
            "category": "o",
            "emoji_order": "1894"
        },
        {
            "name": "pound",
            "unicode": "1f4b7",
            "shortname": ":pound:",
            "code_decimal": "&#128183;",
            "category": "o",
            "emoji_order": "1895"
        },
        {
            "name": "money_with_wings",
            "unicode": "1f4b8",
            "shortname": ":money_with_wings:",
            "code_decimal": "&#128184;",
            "category": "o",
            "emoji_order": "1896"
        },
        {
            "name": "credit_card",
            "unicode": "1f4b3",
            "shortname": ":credit_card:",
            "code_decimal": "&#128179;",
            "category": "o",
            "emoji_order": "1897"
        },
        {
            "name": "chart",
            "unicode": "1f4b9",
            "shortname": ":chart:",
            "code_decimal": "&#128185;",
            "category": "s",
            "emoji_order": "1898"
        },
        {
            "name": "currency_exchange",
            "unicode": "1f4b1",
            "shortname": ":currency_exchange:",
            "code_decimal": "&#128177;",
            "category": "s",
            "emoji_order": "1899"
        },
        {
            "name": "heavy_dollar_sign",
            "unicode": "1f4b2",
            "shortname": ":heavy_dollar_sign:",
            "code_decimal": "&#128178;",
            "category": "s",
            "emoji_order": "1900"
        },
        /* { //@todo not found on image
    "name": "envelope",
    "unicode": "2709",
    "shortname": ":envelope:",
    "code_decimal": "&#9993;",
    "category": "o",
    "emoji_order": "1901"
  },*/
        {
            "name": "e-mail",
            "unicode": "1f4e7",
            "shortname": ":e-mail:",
            "code_decimal": "&#128231;",
            "category": "o",
            "emoji_order": "1902"
        },
        {
            "name": "incoming_envelope",
            "unicode": "1f4e8",
            "shortname": ":incoming_envelope:",
            "code_decimal": "&#128232;",
            "category": "o",
            "emoji_order": "1903"
        },
        {
            "name": "envelope_with_arrow",
            "unicode": "1f4e9",
            "shortname": ":envelope_with_arrow:",
            "code_decimal": "&#128233;",
            "category": "o",
            "emoji_order": "1904"
        },
        {
            "name": "outbox_tray",
            "unicode": "1f4e4",
            "shortname": ":outbox_tray:",
            "code_decimal": "&#128228;",
            "category": "o",
            "emoji_order": "1905"
        },
        {
            "name": "inbox_tray",
            "unicode": "1f4e5",
            "shortname": ":inbox_tray:",
            "code_decimal": "&#128229;",
            "category": "o",
            "emoji_order": "1906"
        },
        {
            "name": "package",
            "unicode": "1f4e6",
            "shortname": ":package:",
            "code_decimal": "&#128230;",
            "category": "o",
            "emoji_order": "1907"
        },
        {
            "name": "mailbox",
            "unicode": "1f4eb",
            "shortname": ":mailbox:",
            "code_decimal": "&#128235;",
            "category": "o",
            "emoji_order": "1908"
        },
        {
            "name": "mailbox_closed",
            "unicode": "1f4ea",
            "shortname": ":mailbox_closed:",
            "code_decimal": "&#128234;",
            "category": "o",
            "emoji_order": "1909"
        },
        {
            "name": "mailbox_with_mail",
            "unicode": "1f4ec",
            "shortname": ":mailbox_with_mail:",
            "code_decimal": "&#128236;",
            "category": "o",
            "emoji_order": "1910"
        },
        {
            "name": "mailbox_with_no_mail",
            "unicode": "1f4ed",
            "shortname": ":mailbox_with_no_mail:",
            "code_decimal": "&#128237;",
            "category": "o",
            "emoji_order": "1911"
        },
        {
            "name": "postbox",
            "unicode": "1f4ee",
            "shortname": ":postbox:",
            "code_decimal": "&#128238;",
            "category": "o",
            "emoji_order": "1912"
        },
        {
            "name": "ballot_box_with_ballot",
            "unicode": "1f5f3",
            "shortname": ":ballot_box:",
            "code_decimal": "&#128499;",
            "category": "o",
            "emoji_order": "1913"
        },
        {
            "name": "pencil2",
            "unicode": "270f",
            "shortname": ":pencil2:",
            "code_decimal": "&#9999;",
            "category": "o",
            "emoji_order": "1914"
        },
        {
            "name": "black_nib",
            "unicode": "2712",
            "shortname": ":black_nib:",
            "code_decimal": "&#10002;",
            "category": "o",
            "emoji_order": "1915"
        },
        {
            "name": "lower_left_fountain_pen",
            "unicode": "1f58b",
            "shortname": ":pen_fountain:",
            "code_decimal": "&#128395;",
            "category": "o",
            "emoji_order": "1916"
        },
        {
            "name": "lower_left_ballpoint_pen",
            "unicode": "1f58a",
            "shortname": ":pen_ballpoint:",
            "code_decimal": "&#128394;",
            "category": "o",
            "emoji_order": "1917"
        },
        {
            "name": "lower_left_paintbrush",
            "unicode": "1f58c",
            "shortname": ":paintbrush:",
            "code_decimal": "&#128396;",
            "category": "o",
            "emoji_order": "1918"
        },
        {
            "name": "lower_left_crayon",
            "unicode": "1f58d",
            "shortname": ":crayon:",
            "code_decimal": "&#128397;",
            "category": "o",
            "emoji_order": "1919"
        },
        {
            "name": "memo",
            "unicode": "1f4dd",
            "shortname": ":pencil:",
            "code_decimal": "&#128221;",
            "category": "o",
            "emoji_order": "1920"
        },
        {
            "name": "briefcase",
            "unicode": "1f4bc",
            "shortname": ":briefcase:",
            "code_decimal": "&#128188;",
            "category": "p",
            "emoji_order": "1921"
        },
        {
            "name": "file_folder",
            "unicode": "1f4c1",
            "shortname": ":file_folder:",
            "code_decimal": "&#128193;",
            "category": "o",
            "emoji_order": "1922"
        },
        {
            "name": "open_file_folder",
            "unicode": "1f4c2",
            "shortname": ":open_file_folder:",
            "code_decimal": "&#128194;",
            "category": "o",
            "emoji_order": "1923"
        },
        {
            "name": "card_index_dividers",
            "unicode": "1f5c2",
            "shortname": ":dividers:",
            "code_decimal": "&#128450;",
            "category": "o",
            "emoji_order": "1924"
        },
        {
            "name": "date",
            "unicode": "1f4c5",
            "shortname": ":date:",
            "code_decimal": "&#128197;",
            "category": "o",
            "emoji_order": "1925"
        },
        {
            "name": "calendar",
            "unicode": "1f4c6",
            "shortname": ":calendar:",
            "code_decimal": "&#128198;",
            "category": "o",
            "emoji_order": "1926"
        },
        {
            "name": "spiral_note_pad",
            "unicode": "1f5d2",
            "shortname": ":notepad_spiral:",
            "code_decimal": "&#128466;",
            "category": "o",
            "emoji_order": "1927"
        },
        {
            "name": "spiral_calendar_pad",
            "unicode": "1f5d3",
            "shortname": ":calendar_spiral:",
            "code_decimal": "&#128467;",
            "category": "o",
            "emoji_order": "1928"
        },
        {
            "name": "card_index",
            "unicode": "1f4c7",
            "shortname": ":card_index:",
            "code_decimal": "&#128199;",
            "category": "o",
            "emoji_order": "1929"
        },
        {
            "name": "chart_with_upwards_trend",
            "unicode": "1f4c8",
            "shortname": ":chart_with_upwards_trend:",
            "code_decimal": "&#128200;",
            "category": "o",
            "emoji_order": "1930"
        },
        {
            "name": "chart_with_downwards_trend",
            "unicode": "1f4c9",
            "shortname": ":chart_with_downwards_trend:",
            "code_decimal": "&#128201;",
            "category": "o",
            "emoji_order": "1931"
        },
        {
            "name": "bar_chart",
            "unicode": "1f4ca",
            "shortname": ":bar_chart:",
            "code_decimal": "&#128202;",
            "category": "o",
            "emoji_order": "1932"
        },
        {
            "name": "clipboard",
            "unicode": "1f4cb",
            "shortname": ":clipboard:",
            "code_decimal": "&#128203;",
            "category": "o",
            "emoji_order": "1933"
        },
        {
            "name": "pushpin",
            "unicode": "1f4cc",
            "shortname": ":pushpin:",
            "code_decimal": "&#128204;",
            "category": "o",
            "emoji_order": "1934"
        },
        {
            "name": "round_pushpin",
            "unicode": "1f4cd",
            "shortname": ":round_pushpin:",
            "code_decimal": "&#128205;",
            "category": "o",
            "emoji_order": "1935"
        },
        {
            "name": "paperclip",
            "unicode": "1f4ce",
            "shortname": ":paperclip:",
            "code_decimal": "&#128206;",
            "category": "o",
            "emoji_order": "1936"
        },
        {
            "name": "linked_paperclips",
            "unicode": "1f587",
            "shortname": ":paperclips:",
            "code_decimal": "&#128391;",
            "category": "o",
            "emoji_order": "1937"
        },
        {
            "name": "straight_ruler",
            "unicode": "1f4cf",
            "shortname": ":straight_ruler:",
            "code_decimal": "&#128207;",
            "category": "o",
            "emoji_order": "1938"
        },
        {
            "name": "triangular_ruler",
            "unicode": "1f4d0",
            "shortname": ":triangular_ruler:",
            "code_decimal": "&#128208;",
            "category": "o",
            "emoji_order": "1939"
        },
        {
            "name": "scissors",
            "unicode": "2702",
            "shortname": ":scissors:",
            "code_decimal": "&#9986;",
            "category": "o",
            "emoji_order": "1940"
        },
        {
            "name": "card_file_box",
            "unicode": "1f5c3",
            "shortname": ":card_box:",
            "code_decimal": "&#128451;",
            "category": "o",
            "emoji_order": "1941"
        },
        {
            "name": "file_cabinet",
            "unicode": "1f5c4",
            "shortname": ":file_cabinet:",
            "code_decimal": "&#128452;",
            "category": "o",
            "emoji_order": "1942"
        },
        {
            "name": "wastebasket",
            "unicode": "1f5d1",
            "shortname": ":wastebasket:",
            "code_decimal": "&#128465;",
            "category": "o",
            "emoji_order": "1943"
        },
        {
            "name": "lock",
            "unicode": "1f512",
            "shortname": ":lock:",
            "code_decimal": "&#128274;",
            "category": "o",
            "emoji_order": "1944"
        },
        {
            "name": "unlock",
            "unicode": "1f513",
            "shortname": ":unlock:",
            "code_decimal": "&#128275;",
            "category": "o",
            "emoji_order": "1945"
        },
        {
            "name": "lock_with_ink_pen",
            "unicode": "1f50f",
            "shortname": ":lock_with_ink_pen:",
            "code_decimal": "&#128271;",
            "category": "o",
            "emoji_order": "1946"
        },
        {
            "name": "closed_lock_with_key",
            "unicode": "1f510",
            "shortname": ":closed_lock_with_key:",
            "code_decimal": "&#128272;",
            "category": "o",
            "emoji_order": "1947"
        },
        {
            "name": "key",
            "unicode": "1f511",
            "shortname": ":key:",
            "code_decimal": "&#128273;",
            "category": "o",
            "emoji_order": "1948"
        },
        {
            "name": "old_key",
            "unicode": "1f5dd",
            "shortname": ":key2:",
            "code_decimal": "&#128477;",
            "category": "o",
            "emoji_order": "1949"
        },
        {
            "name": "hammer",
            "unicode": "1f528",
            "shortname": ":hammer:",
            "code_decimal": "&#128296;",
            "category": "o",
            "emoji_order": "1950"
        },
        {
            "name": "pick",
            "unicode": "26cf",
            "shortname": ":pick:",
            "code_decimal": "&#9935;",
            "category": "o",
            "emoji_order": "1951"
        },
        {
            "name": "hammer_and_pick",
            "unicode": "2692",
            "shortname": ":hammer_pick:",
            "code_decimal": "&#9874;",
            "category": "o",
            "emoji_order": "1952"
        },
        {
            "name": "hammer_and_wrench",
            "unicode": "1f6e0",
            "shortname": ":tools:",
            "code_decimal": "&#128736;",
            "category": "o",
            "emoji_order": "1953"
        },
        {
            "name": "dagger_knife",
            "unicode": "1f5e1",
            "shortname": ":dagger:",
            "code_decimal": "&#128481;",
            "category": "o",
            "emoji_order": "1954"
        },
        {
            "name": "crossed_swords",
            "unicode": "2694",
            "shortname": ":crossed_swords:",
            "code_decimal": "&#9876;",
            "category": "o",
            "emoji_order": "1955"
        },
        {
            "name": "gun",
            "unicode": "1f52b",
            "shortname": ":gun:",
            "code_decimal": "&#128299;",
            "category": "o",
            "emoji_order": "1956"
        },
        {
            "name": "bow_and_arrow",
            "unicode": "1f3f9",
            "shortname": ":bow_and_arrow:",
            "code_decimal": "&#127993;",
            "category": "a",
            "emoji_order": "1957"
        },
        {
            "name": "shield",
            "unicode": "1f6e1",
            "shortname": ":shield:",
            "code_decimal": "&#128737;",
            "category": "o",
            "emoji_order": "1958"
        },
        {
            "name": "wrench",
            "unicode": "1f527",
            "shortname": ":wrench:",
            "code_decimal": "&#128295;",
            "category": "o",
            "emoji_order": "1959"
        },
        {
            "name": "nut_and_bolt",
            "unicode": "1f529",
            "shortname": ":nut_and_bolt:",
            "code_decimal": "&#128297;",
            "category": "o",
            "emoji_order": "1960"
        },
        {
            "name": "gear",
            "unicode": "2699",
            "shortname": ":gear:",
            "code_decimal": "&#9881;",
            "category": "o",
            "emoji_order": "1961"
        },
        {
            "name": "compression",
            "unicode": "1f5dc",
            "shortname": ":compression:",
            "code_decimal": "&#128476;",
            "category": "o",
            "emoji_order": "1962"
        },
        {
            "name": "alembic",
            "unicode": "2697",
            "shortname": ":alembic:",
            "code_decimal": "&#9879;",
            "category": "o",
            "emoji_order": "1963"
        },
        {
            "name": "scales",
            "unicode": "2696",
            "shortname": ":scales:",
            "code_decimal": "&#9878;",
            "category": "o",
            "emoji_order": "1964"
        },
        {
            "name": "link",
            "unicode": "1f517",
            "shortname": ":link:",
            "code_decimal": "&#128279;",
            "category": "o",
            "emoji_order": "1965"
        },
        {
            "name": "chains",
            "unicode": "26d3",
            "shortname": ":chains:",
            "code_decimal": "&#9939;",
            "category": "o",
            "emoji_order": "1966"
        },
        {
            "name": "syringe",
            "unicode": "1f489",
            "shortname": ":syringe:",
            "code_decimal": "&#128137;",
            "category": "o",
            "emoji_order": "1967"
        },
        {
            "name": "pill",
            "unicode": "1f48a",
            "shortname": ":pill:",
            "code_decimal": "&#128138;",
            "category": "o",
            "emoji_order": "1968"
        },
        {
            "name": "smoking",
            "unicode": "1f6ac",
            "shortname": ":smoking:",
            "code_decimal": "&#128684;",
            "category": "o",
            "emoji_order": "1969"
        },
        {
            "name": "coffin",
            "unicode": "26b0",
            "shortname": ":coffin:",
            "code_decimal": "&#9904;",
            "category": "o",
            "emoji_order": "1970"
        },
        {
            "name": "funeral_urn",
            "unicode": "26b1",
            "shortname": ":urn:",
            "code_decimal": "&#9905;",
            "category": "o",
            "emoji_order": "1971"
        },
        {
            "name": "moyai",
            "unicode": "1f5ff",
            "shortname": ":moyai:",
            "code_decimal": "&#128511;",
            "category": "o",
            "emoji_order": "1972"
        },
        {
            "name": "oil_drum",
            "unicode": "1f6e2",
            "shortname": ":oil:",
            "code_decimal": "&#128738;",
            "category": "o",
            "emoji_order": "1973"
        },
        {
            "name": "crystal_ball",
            "unicode": "1f52e",
            "shortname": ":crystal_ball:",
            "code_decimal": "&#128302;",
            "category": "o",
            "emoji_order": "1974"
        },
        /*{ //@todo not found on image
    "name": "shopping_cart",
    "unicode": "1f6d2",
    "shortname": ":shopping_cart:",
    "code_decimal": "&#128722;",
    "category": "o",
    "emoji_order": "1975"
  },*/
        {
            "name": "atm",
            "unicode": "1f3e7",
            "shortname": ":atm:",
            "code_decimal": "&#127975;",
            "category": "s",
            "emoji_order": "1976"
        },
        {
            "name": "put_litter_in_its_place",
            "unicode": "1f6ae",
            "shortname": ":put_litter_in_its_place:",
            "code_decimal": "&#128686;",
            "category": "s",
            "emoji_order": "1977"
        },
        {
            "name": "potable_water",
            "unicode": "1f6b0",
            "shortname": ":potable_water:",
            "code_decimal": "&#128688;",
            "category": "s",
            "emoji_order": "1978"
        },
        {
            "name": "wheelchair",
            "unicode": "267f",
            "shortname": ":wheelchair:",
            "code_decimal": "&#9855;",
            "category": "s",
            "emoji_order": "1979"
        },
        {
            "name": "mens",
            "unicode": "1f6b9",
            "shortname": ":mens:",
            "code_decimal": "&#128697;",
            "category": "s",
            "emoji_order": "1980"
        },
        {
            "name": "womens",
            "unicode": "1f6ba",
            "shortname": ":womens:",
            "code_decimal": "&#128698;",
            "category": "s",
            "emoji_order": "1981"
        },
        {
            "name": "restroom",
            "unicode": "1f6bb",
            "shortname": ":restroom:",
            "code_decimal": "&#128699;",
            "category": "s",
            "emoji_order": "1982"
        },
        {
            "name": "baby_symbol",
            "unicode": "1f6bc",
            "shortname": ":baby_symbol:",
            "code_decimal": "&#128700;",
            "category": "s",
            "emoji_order": "1983"
        },
        {
            "name": "wc",
            "unicode": "1f6be",
            "shortname": ":wc:",
            "code_decimal": "&#128702;",
            "category": "s",
            "emoji_order": "1984"
        },
        {
            "name": "passport_control",
            "unicode": "1f6c2",
            "shortname": ":passport_control:",
            "code_decimal": "&#128706;",
            "category": "s",
            "emoji_order": "1985"
        },
        {
            "name": "customs",
            "unicode": "1f6c3",
            "shortname": ":customs:",
            "code_decimal": "&#128707;",
            "category": "s",
            "emoji_order": "1986"
        },
        {
            "name": "baggage_claim",
            "unicode": "1f6c4",
            "shortname": ":baggage_claim:",
            "code_decimal": "&#128708;",
            "category": "s",
            "emoji_order": "1987"
        },
        {
            "name": "left_luggage",
            "unicode": "1f6c5",
            "shortname": ":left_luggage:",
            "code_decimal": "&#128709;",
            "category": "s",
            "emoji_order": "1988"
        },
        {
            "name": "warning",
            "unicode": "26a0",
            "shortname": ":warning:",
            "code_decimal": "&#9888;",
            "category": "s",
            "emoji_order": "1989"
        },
        {
            "name": "children_crossing",
            "unicode": "1f6b8",
            "shortname": ":children_crossing:",
            "code_decimal": "&#128696;",
            "category": "s",
            "emoji_order": "1990"
        },
        {
            "name": "no_entry",
            "unicode": "26d4",
            "shortname": ":no_entry:",
            "code_decimal": "&#9940;",
            "category": "s",
            "emoji_order": "1991"
        },
        {
            "name": "no_entry_sign",
            "unicode": "1f6ab",
            "shortname": ":no_entry_sign:",
            "code_decimal": "&#128683;",
            "category": "s",
            "emoji_order": "1992"
        },
        {
            "name": "no_bicycles",
            "unicode": "1f6b3",
            "shortname": ":no_bicycles:",
            "code_decimal": "&#128691;",
            "category": "s",
            "emoji_order": "1993"
        },
        {
            "name": "no_smoking",
            "unicode": "1f6ad",
            "shortname": ":no_smoking:",
            "code_decimal": "&#128685;",
            "category": "s",
            "emoji_order": "1994"
        },
        {
            "name": "do_not_litter",
            "unicode": "1f6af",
            "shortname": ":do_not_litter:",
            "code_decimal": "&#128687;",
            "category": "s",
            "emoji_order": "1995"
        },
        {
            "name": "non-potable_water",
            "unicode": "1f6b1",
            "shortname": ":non-potable_water:",
            "code_decimal": "&#128689;",
            "category": "s",
            "emoji_order": "1996"
        },
        {
            "name": "no_pedestrians",
            "unicode": "1f6b7",
            "shortname": ":no_pedestrians:",
            "code_decimal": "&#128695;",
            "category": "s",
            "emoji_order": "1997"
        },
        {
            "name": "no_mobile_phones",
            "unicode": "1f4f5",
            "shortname": ":no_mobile_phones:",
            "code_decimal": "&#128245;",
            "category": "s",
            "emoji_order": "1998"
        },
        {
            "name": "underage",
            "unicode": "1f51e",
            "shortname": ":underage:",
            "code_decimal": "&#128286;",
            "category": "s",
            "emoji_order": "1999"
        },
        {
            "name": "radioactive",
            "unicode": "2622",
            "shortname": ":radioactive:",
            "code_decimal": "&#9762;",
            "category": "s",
            "emoji_order": "2000"
        },
        {
            "name": "biohazard",
            "unicode": "2623",
            "shortname": ":biohazard:",
            "code_decimal": "&#9763;",
            "category": "s",
            "emoji_order": "2001"
        },
        {
            "name": "arrow_up",
            "unicode": "2b06",
            "shortname": ":arrow_up:",
            "code_decimal": "&#11014;",
            "category": "s",
            "emoji_order": "2002"
        },
        {
            "name": "arrow_upper_right",
            "unicode": "2197",
            "shortname": ":arrow_upper_right:",
            "code_decimal": "&#8599;",
            "category": "s",
            "emoji_order": "2003"
        },
        {
            "name": "arrow_right",
            "unicode": "27a1",
            "shortname": ":arrow_right:",
            "code_decimal": "&#10145;",
            "category": "s",
            "emoji_order": "2004"
        },
        {
            "name": "arrow_lower_right",
            "unicode": "2198",
            "shortname": ":arrow_lower_right:",
            "code_decimal": "&#8600;",
            "category": "s",
            "emoji_order": "2005"
        },
        {
            "name": "arrow_down",
            "unicode": "2b07",
            "shortname": ":arrow_down:",
            "code_decimal": "&#11015;",
            "category": "s",
            "emoji_order": "2006"
        },
        {
            "name": "arrow_lower_left",
            "unicode": "2199",
            "shortname": ":arrow_lower_left:",
            "code_decimal": "&#8601;",
            "category": "s",
            "emoji_order": "2007"
        },
        {
            "name": "arrow_left",
            "unicode": "2b05",
            "shortname": ":arrow_left:",
            "code_decimal": "&#11013;",
            "category": "s",
            "emoji_order": "2008"
        },
        {
            "name": "arrow_upper_left",
            "unicode": "2196",
            "shortname": ":arrow_upper_left:",
            "code_decimal": "&#8598;",
            "category": "s",
            "emoji_order": "2009"
        },
        {
            "name": "arrow_up_down",
            "unicode": "2195",
            "shortname": ":arrow_up_down:",
            "code_decimal": "&#8597;",
            "category": "s",
            "emoji_order": "2010"
        },
        {
            "name": "left_right_arrow",
            "unicode": "2194",
            "shortname": ":left_right_arrow:",
            "code_decimal": "&harr;",
            "category": "s",
            "emoji_order": "2011"
        },
        {
            "name": "leftwards_arrow_with_hook",
            "unicode": "21a9",
            "shortname": ":leftwards_arrow_with_hook:",
            "code_decimal": "&#8617;",
            "category": "s",
            "emoji_order": "2012"
        },
        {
            "name": "arrow_right_hook",
            "unicode": "21aa",
            "shortname": ":arrow_right_hook:",
            "code_decimal": "&#8618;",
            "category": "s",
            "emoji_order": "2013"
        },
        {
            "name": "arrow_heading_up",
            "unicode": "2934",
            "shortname": ":arrow_heading_up:",
            "code_decimal": "&#10548;",
            "category": "s",
            "emoji_order": "2014"
        },
        {
            "name": "arrow_heading_down",
            "unicode": "2935",
            "shortname": ":arrow_heading_down:",
            "code_decimal": "&#10549;",
            "category": "s",
            "emoji_order": "2015"
        },
        {
            "name": "arrows_clockwise",
            "unicode": "1f503",
            "shortname": ":arrows_clockwise:",
            "code_decimal": "&#128259;",
            "category": "s",
            "emoji_order": "2016"
        },
        {
            "name": "arrows_counterclockwise",
            "unicode": "1f504",
            "shortname": ":arrows_counterclockwise:",
            "code_decimal": "&#128260;",
            "category": "s",
            "emoji_order": "2017"
        },
        {
            "name": "back",
            "unicode": "1f519",
            "shortname": ":back:",
            "code_decimal": "&#128281;",
            "category": "s",
            "emoji_order": "2018"
        },
        {
            "name": "end",
            "unicode": "1f51a",
            "shortname": ":end:",
            "code_decimal": "&#128282;",
            "category": "s",
            "emoji_order": "2019"
        },
        {
            "name": "on",
            "unicode": "1f51b",
            "shortname": ":on:",
            "code_decimal": "&#128283;",
            "category": "s",
            "emoji_order": "2020"
        },
        {
            "name": "soon",
            "unicode": "1f51c",
            "shortname": ":soon:",
            "code_decimal": "&#128284;",
            "category": "s",
            "emoji_order": "2021"
        },
        {
            "name": "top",
            "unicode": "1f51d",
            "shortname": ":top:",
            "code_decimal": "&#128285;",
            "category": "s",
            "emoji_order": "2022"
        },
        {
            "name": "place_of_worship",
            "unicode": "1f6d0",
            "shortname": ":place_of_worship:",
            "code_decimal": "&#128720;",
            "category": "s",
            "emoji_order": "2023"
        },
        {
            "name": "atom_symbol",
            "unicode": "269b",
            "shortname": ":atom:",
            "code_decimal": "&#9883;",
            "category": "s",
            "emoji_order": "2024"
        },
        {
            "name": "om_symbol",
            "unicode": "1f549",
            "shortname": ":om_symbol:",
            "code_decimal": "&#128329;",
            "category": "s",
            "emoji_order": "2025"
        },
        {
            "name": "star_of_david",
            "unicode": "2721",
            "shortname": ":star_of_david:",
            "code_decimal": "&#10017;",
            "category": "s",
            "emoji_order": "2026"
        },
        {
            "name": "wheel_of_dharma",
            "unicode": "2638",
            "shortname": ":wheel_of_dharma:",
            "code_decimal": "&#9784;",
            "category": "s",
            "emoji_order": "2027"
        },
        {
            "name": "yin_yang",
            "unicode": "262f",
            "shortname": ":yin_yang:",
            "code_decimal": "&#9775;",
            "category": "s",
            "emoji_order": "2028"
        },
        {
            "name": "latin_cross",
            "unicode": "271d",
            "shortname": ":cross:",
            "code_decimal": "&#10013;",
            "category": "s",
            "emoji_order": "2029"
        },
        {
            "name": "orthodox_cross",
            "unicode": "2626",
            "shortname": ":orthodox_cross:",
            "code_decimal": "&#9766;",
            "category": "s",
            "emoji_order": "2030"
        },
        {
            "name": "star_and_crescent",
            "unicode": "262a",
            "shortname": ":star_and_crescent:",
            "code_decimal": "&#9770;",
            "category": "s",
            "emoji_order": "2031"
        },
        {
            "name": "peace_symbol",
            "unicode": "262e",
            "shortname": ":peace:",
            "code_decimal": "&#9774;",
            "category": "s",
            "emoji_order": "2032"
        },
        {
            "name": "menorah_with_nine_branches",
            "unicode": "1f54e",
            "shortname": ":menorah:",
            "code_decimal": "&#128334;",
            "category": "s",
            "emoji_order": "2033"
        },
        {
            "name": "six_pointed_star",
            "unicode": "1f52f",
            "shortname": ":six_pointed_star:",
            "code_decimal": "&#128303;",
            "category": "s",
            "emoji_order": "2034"
        },
        {
            "name": "aries",
            "unicode": "2648",
            "shortname": ":aries:",
            "code_decimal": "&#9800;",
            "category": "s",
            "emoji_order": "2035"
        },
        {
            "name": "taurus",
            "unicode": "2649",
            "shortname": ":taurus:",
            "code_decimal": "&#9801;",
            "category": "s",
            "emoji_order": "2036"
        },
        {
            "name": "gemini",
            "unicode": "264a",
            "shortname": ":gemini:",
            "code_decimal": "&#9802;",
            "category": "s",
            "emoji_order": "2037"
        },
        {
            "name": "cancer",
            "unicode": "264b",
            "shortname": ":cancer:",
            "code_decimal": "&#9803;",
            "category": "s",
            "emoji_order": "2038"
        },
        {
            "name": "leo",
            "unicode": "264c",
            "shortname": ":leo:",
            "code_decimal": "&#9804;",
            "category": "s",
            "emoji_order": "2039"
        },
        {
            "name": "virgo",
            "unicode": "264d",
            "shortname": ":virgo:",
            "code_decimal": "&#9805;",
            "category": "s",
            "emoji_order": "2040"
        },
        {
            "name": "libra",
            "unicode": "264e",
            "shortname": ":libra:",
            "code_decimal": "&#9806;",
            "category": "s",
            "emoji_order": "2041"
        },
        {
            "name": "scorpius",
            "unicode": "264f",
            "shortname": ":scorpius:",
            "code_decimal": "&#9807;",
            "category": "s",
            "emoji_order": "2042"
        },
        {
            "name": "sagittarius",
            "unicode": "2650",
            "shortname": ":sagittarius:",
            "code_decimal": "&#9808;",
            "category": "s",
            "emoji_order": "2043"
        },
        {
            "name": "capricorn",
            "unicode": "2651",
            "shortname": ":capricorn:",
            "code_decimal": "&#9809;",
            "category": "s",
            "emoji_order": "2044"
        },
        {
            "name": "aquarius",
            "unicode": "2652",
            "shortname": ":aquarius:",
            "code_decimal": "&#9810;",
            "category": "s",
            "emoji_order": "2045"
        },
        {
            "name": "pisces",
            "unicode": "2653",
            "shortname": ":pisces:",
            "code_decimal": "&#9811;",
            "category": "s",
            "emoji_order": "2046"
        },
        {
            "name": "ophiuchus",
            "unicode": "26ce",
            "shortname": ":ophiuchus:",
            "code_decimal": "&#9934;",
            "category": "s",
            "emoji_order": "2047"
        },
        {
            "name": "twisted_rightwards_arrows",
            "unicode": "1f500",
            "shortname": ":twisted_rightwards_arrows:",
            "code_decimal": "&#128256;",
            "category": "s",
            "emoji_order": "2048"
        },
        {
            "name": "repeat",
            "unicode": "1f501",
            "shortname": ":repeat:",
            "code_decimal": "&#128257;",
            "category": "s",
            "emoji_order": "2049"
        },
        {
            "name": "repeat_one",
            "unicode": "1f502",
            "shortname": ":repeat_one:",
            "code_decimal": "&#128258;",
            "category": "s",
            "emoji_order": "2050"
        },
        {
            "name": "arrow_forward",
            "unicode": "25b6",
            "shortname": ":arrow_forward:",
            "code_decimal": "&#9654;",
            "category": "s",
            "emoji_order": "2051"
        },
        {
            "name": "fast_forward",
            "unicode": "23e9",
            "shortname": ":fast_forward:",
            "code_decimal": "&#9193;",
            "category": "s",
            "emoji_order": "2052"
        },
        {
            "name": "black_right_pointing_double_triangle_with_vertical_bar",
            "unicode": "23ed",
            "shortname": ":track_next:",
            "code_decimal": "&#9197;",
            "category": "s",
            "emoji_order": "2053"
        },
        {
            "name": "black_right_pointing_triangle_with_double_vertical_bar",
            "unicode": "23ef",
            "shortname": ":play_pause:",
            "code_decimal": "&#9199;",
            "category": "s",
            "emoji_order": "2054"
        },
        {
            "name": "arrow_backward",
            "unicode": "25c0",
            "shortname": ":arrow_backward:",
            "code_decimal": "&#9664;",
            "category": "s",
            "emoji_order": "2055"
        },
        {
            "name": "rewind",
            "unicode": "23ea",
            "shortname": ":rewind:",
            "code_decimal": "&#9194;",
            "category": "s",
            "emoji_order": "2056"
        },
        {
            "name": "black_left_pointing_double_triangle_with_vertical_bar",
            "unicode": "23ee",
            "shortname": ":track_previous:",
            "code_decimal": "&#9198;",
            "category": "s",
            "emoji_order": "2057"
        },
        {
            "name": "arrow_up_small",
            "unicode": "1f53c",
            "shortname": ":arrow_up_small:",
            "code_decimal": "&#128316;",
            "category": "s",
            "emoji_order": "2058"
        },
        {
            "name": "arrow_double_up",
            "unicode": "23eb",
            "shortname": ":arrow_double_up:",
            "code_decimal": "&#9195;",
            "category": "s",
            "emoji_order": "2059"
        },
        {
            "name": "arrow_down_small",
            "unicode": "1f53d",
            "shortname": ":arrow_down_small:",
            "code_decimal": "&#128317;",
            "category": "s",
            "emoji_order": "2060"
        },
        {
            "name": "arrow_double_down",
            "unicode": "23ec",
            "shortname": ":arrow_double_down:",
            "code_decimal": "&#9196;",
            "category": "s",
            "emoji_order": "2061"
        },
        {
            "name": "double_vertical_bar",
            "unicode": "23f8",
            "shortname": ":pause_button:",
            "code_decimal": "&#9208;",
            "category": "s",
            "emoji_order": "2062"
        },
        {
            "name": "black_square_for_stop",
            "unicode": "23f9",
            "shortname": ":stop_button:",
            "code_decimal": "&#9209;",
            "category": "s",
            "emoji_order": "2063"
        },
        {
            "name": "black_circle_for_record",
            "unicode": "23fa",
            "shortname": ":record_button:",
            "code_decimal": "&#9210;",
            "category": "s",
            "emoji_order": "2064"
        },
        /*{ //@todo not found on image
    "name": "eject",
    "unicode": "23cf",
    "shortname": ":eject:",
    "code_decimal": "&#9167;",
    "category": "s",
    "emoji_order": "2065"
  },*/
        {
            "name": "cinema",
            "unicode": "1f3a6",
            "shortname": ":cinema:",
            "code_decimal": "&#127910;",
            "category": "s",
            "emoji_order": "2066"
        },
        {
            "name": "low_brightness",
            "unicode": "1f505",
            "shortname": ":low_brightness:",
            "code_decimal": "&#128261;",
            "category": "s",
            "emoji_order": "2067"
        },
        {
            "name": "high_brightness",
            "unicode": "1f506",
            "shortname": ":high_brightness:",
            "code_decimal": "&#128262;",
            "category": "s",
            "emoji_order": "2068"
        },
        {
            "name": "signal_strength",
            "unicode": "1f4f6",
            "shortname": ":signal_strength:",
            "code_decimal": "&#128246;",
            "category": "s",
            "emoji_order": "2069"
        },
        {
            "name": "vibration_mode",
            "unicode": "1f4f3",
            "shortname": ":vibration_mode:",
            "code_decimal": "&#128243;",
            "category": "s",
            "emoji_order": "2070"
        },
        {
            "name": "mobile_phone_off",
            "unicode": "1f4f4",
            "shortname": ":mobile_phone_off:",
            "code_decimal": "&#128244;",
            "category": "s",
            "emoji_order": "2071"
        },
        {
            "name": "recycle",
            "unicode": "267b",
            "shortname": ":recycle:",
            "code_decimal": "&#9851;",
            "category": "s",
            "emoji_order": "2072"
        },
        {
            "name": "name_badge",
            "unicode": "1f4db",
            "shortname": ":name_badge:",
            "code_decimal": "&#128219;",
            "category": "s",
            "emoji_order": "2073"
        },
        {
            "name": "fleur_de_lis",
            "unicode": "269c",
            "shortname": ":fleur-de-lis:",
            "code_decimal": "&#9884;",
            "category": "s",
            "emoji_order": "2074"
        },
        {
            "name": "beginner",
            "unicode": "1f530",
            "shortname": ":beginner:",
            "code_decimal": "&#128304;",
            "category": "s",
            "emoji_order": "2075"
        },
        {
            "name": "trident",
            "unicode": "1f531",
            "shortname": ":trident:",
            "code_decimal": "&#128305;",
            "category": "s",
            "emoji_order": "2076"
        },
        {
            "name": "o",
            "unicode": "2b55",
            "shortname": ":o:",
            "code_decimal": "&#11093;",
            "category": "s",
            "emoji_order": "2077"
        },
        {
            "name": "white_check_mark",
            "unicode": "2705",
            "shortname": ":white_check_mark:",
            "code_decimal": "&#9989;",
            "category": "s",
            "emoji_order": "2078"
        },
        {
            "name": "ballot_box_with_check",
            "unicode": "2611",
            "shortname": ":ballot_box_with_check:",
            "code_decimal": "&#9745;",
            "category": "s",
            "emoji_order": "2079"
        },
        {
            "name": "heavy_check_mark",
            "unicode": "2714",
            "shortname": ":heavy_check_mark:",
            "code_decimal": "&#10004;",
            "category": "s",
            "emoji_order": "2080"
        },
        {
            "name": "heavy_multiplication_x",
            "unicode": "2716",
            "shortname": ":heavy_multiplication_x:",
            "code_decimal": "&#10006;",
            "category": "s",
            "emoji_order": "2081"
        },
        {
            "name": "x",
            "unicode": "274c",
            "shortname": ":x:",
            "code_decimal": "&#10060;",
            "category": "s",
            "emoji_order": "2082"
        },
        {
            "name": "negative_squared_cross_mark",
            "unicode": "274e",
            "shortname": ":negative_squared_cross_mark:",
            "code_decimal": "&#10062;",
            "category": "s",
            "emoji_order": "2083"
        },
        {
            "name": "heavy_plus_sign",
            "unicode": "2795",
            "shortname": ":heavy_plus_sign:",
            "code_decimal": "&#10133;",
            "category": "s",
            "emoji_order": "2084"
        },
        {
            "name": "heavy_minus_sign",
            "unicode": "2796",
            "shortname": ":heavy_minus_sign:",
            "code_decimal": "&#10134;",
            "category": "s",
            "emoji_order": "2088"
        },
        {
            "name": "heavy_division_sign",
            "unicode": "2797",
            "shortname": ":heavy_division_sign:",
            "code_decimal": "&#10135;",
            "category": "s",
            "emoji_order": "2089"
        },
        {
            "name": "curly_loop",
            "unicode": "27b0",
            "shortname": ":curly_loop:",
            "code_decimal": "&#10160;",
            "category": "s",
            "emoji_order": "2090"
        },
        {
            "name": "loop",
            "unicode": "27bf",
            "shortname": ":loop:",
            "code_decimal": "&#10175;",
            "category": "s",
            "emoji_order": "2091"
        },
        {
            "name": "part_alternation_mark",
            "unicode": "303d",
            "shortname": ":part_alternation_mark:",
            "code_decimal": "&#12349;",
            "category": "s",
            "emoji_order": "2092"
        },
        {
            "name": "eight_spoked_asterisk",
            "unicode": "2733",
            "shortname": ":eight_spoked_asterisk:",
            "code_decimal": "&#10035;",
            "category": "s",
            "emoji_order": "2093"
        },
        {
            "name": "eight_pointed_black_star",
            "unicode": "2734",
            "shortname": ":eight_pointed_black_star:",
            "code_decimal": "&#10036;",
            "category": "s",
            "emoji_order": "2094"
        },
        {
            "name": "sparkle",
            "unicode": "2747",
            "shortname": ":sparkle:",
            "code_decimal": "&#10055;",
            "category": "s",
            "emoji_order": "2095"
        },
        {
            "name": "bangbang",
            "unicode": "203c",
            "shortname": ":bangbang:",
            "code_decimal": "&#8252;",
            "category": "s",
            "emoji_order": "2096"
        },
        {
            "name": "interrobang",
            "unicode": "2049",
            "shortname": ":interrobang:",
            "code_decimal": "&#8265;",
            "category": "s",
            "emoji_order": "2097"
        },
        {
            "name": "question",
            "unicode": "2753",
            "shortname": ":question:",
            "code_decimal": "&#10067;",
            "category": "s",
            "emoji_order": "2098"
        },
        {
            "name": "grey_question",
            "unicode": "2754",
            "shortname": ":grey_question:",
            "code_decimal": "&#10068;",
            "category": "s",
            "emoji_order": "2099"
        },
        {
            "name": "grey_exclamation",
            "unicode": "2755",
            "shortname": ":grey_exclamation:",
            "code_decimal": "&#10069;",
            "category": "s",
            "emoji_order": "2100"
        },
        {
            "name": "exclamation",
            "unicode": "2757",
            "shortname": ":exclamation:",
            "code_decimal": "&#10071;",
            "category": "s",
            "emoji_order": "2101"
        },
        {
            "name": "wavy_dash",
            "unicode": "3030",
            "shortname": ":wavy_dash:",
            "code_decimal": "&#12336;",
            "category": "s",
            "emoji_order": "2102"
        },
        {
            "name": "copyright",
            "unicode": "00a9",
            "shortname": ":copyright:",
            "code_decimal": "&copy;",
            "category": "s",
            "emoji_order": "2103"
        },
        {
            "name": "registered",
            "unicode": "00ae",
            "shortname": ":registered:",
            "code_decimal": "&reg;",
            "category": "s",
            "emoji_order": "2104"
        },
        {
            "name": "tm",
            "unicode": "2122",
            "shortname": ":tm:",
            "code_decimal": "&trade;",
            "category": "s",
            "emoji_order": "2105"
        },
        {
            "name": "hash",
            "unicode": "0023-fe0f-20e3",
            "shortname": ":hash:",
            "code_decimal": "&#35;&#65039;&#8419;",
            "category": "s",
            "emoji_order": "2106"
        },
        {
            "name": "keycap_star",
            "unicode": "002a-fe0f-20e3",
            "shortname": ":asterisk:",
            "code_decimal": "&#42;&#65039;&#8419;",
            "category": "s",
            "emoji_order": "2107"
        },
        {
            "name": "zero",
            "unicode": "0030-fe0f-20e3",
            "shortname": ":zero:",
            "code_decimal": "&#48;&#65039;&#8419;",
            "category": "s",
            "emoji_order": "2108"
        },
        {
            "name": "one",
            "unicode": "0031-fe0f-20e3",
            "shortname": ":one:",
            "code_decimal": "&#49;&#65039;&#8419;",
            "category": "s",
            "emoji_order": "2109"
        },
        {
            "name": "two",
            "unicode": "0032-fe0f-20e3",
            "shortname": ":two:",
            "code_decimal": "&#50;&#65039;&#8419;",
            "category": "s",
            "emoji_order": "2110"
        },
        {
            "name": "three",
            "unicode": "0033-fe0f-20e3",
            "shortname": ":three:",
            "code_decimal": "&#51;&#65039;&#8419;",
            "category": "s",
            "emoji_order": "2111"
        },
        {
            "name": "four",
            "unicode": "0034-fe0f-20e3",
            "shortname": ":four:",
            "code_decimal": "&#52;&#65039;&#8419;",
            "category": "s",
            "emoji_order": "2112"
        },
        {
            "name": "five",
            "unicode": "0035-fe0f-20e3",
            "shortname": ":five:",
            "code_decimal": "&#53;&#65039;&#8419;",
            "category": "s",
            "emoji_order": "2113"
        },
        {
            "name": "six",
            "unicode": "0036-fe0f-20e3",
            "shortname": ":six:",
            "code_decimal": "&#54;&#65039;&#8419;",
            "category": "s",
            "emoji_order": "2114"
        },
        {
            "name": "seven",
            "unicode": "0037-fe0f-20e3",
            "shortname": ":seven:",
            "code_decimal": "&#55;&#65039;&#8419;",
            "category": "s",
            "emoji_order": "2115"
        },
        {
            "name": "eight",
            "unicode": "0038-fe0f-20e3",
            "shortname": ":eight:",
            "code_decimal": "&#56;&#65039;&#8419;",
            "category": "s",
            "emoji_order": "2116"
        },
        {
            "name": "nine",
            "unicode": "0039-fe0f-20e3",
            "shortname": ":nine:",
            "code_decimal": "&#57;&#65039;&#8419;",
            "category": "s",
            "emoji_order": "2117"
        },
        {
            "name": "keycap_ten",
            "unicode": "1f51f",
            "shortname": ":keycap_ten:",
            "code_decimal": "&#128287;",
            "category": "s",
            "emoji_order": "2118"
        },
        {
            "name": "capital_abcd",
            "unicode": "1f520",
            "shortname": ":capital_abcd:",
            "code_decimal": "&#128288;",
            "category": "s",
            "emoji_order": "2120"
        },
        {
            "name": "abcd",
            "unicode": "1f521",
            "shortname": ":abcd:",
            "code_decimal": "&#128289;",
            "category": "s",
            "emoji_order": "2121"
        },
        {
            "name": "s",
            "unicode": "1f523",
            "shortname": ":s:",
            "code_decimal": "&#128291;",
            "category": "s",
            "emoji_order": "2123"
        },
        {
            "name": "abc",
            "unicode": "1f524",
            "shortname": ":abc:",
            "code_decimal": "&#128292;",
            "category": "s",
            "emoji_order": "2124"
        },
        {
            "name": "a",
            "unicode": "1f170",
            "shortname": ":a:",
            "code_decimal": "&#127344;",
            "category": "s",
            "emoji_order": "2125"
        },
        {
            "name": "ab",
            "unicode": "1f18e",
            "shortname": ":ab:",
            "code_decimal": "&#127374;",
            "category": "s",
            "emoji_order": "2126"
        },
        {
            "name": "b",
            "unicode": "1f171",
            "shortname": ":b:",
            "code_decimal": "&#127345;",
            "category": "s",
            "emoji_order": "2127"
        },
        {
            "name": "cl",
            "unicode": "1f191",
            "shortname": ":cl:",
            "code_decimal": "&#127377;",
            "category": "s",
            "emoji_order": "2128"
        },
        {
            "name": "cool",
            "unicode": "1f192",
            "shortname": ":cool:",
            "code_decimal": "&#127378;",
            "category": "s",
            "emoji_order": "2129"
        },
        {
            "name": "free",
            "unicode": "1f193",
            "shortname": ":free:",
            "code_decimal": "&#127379;",
            "category": "s",
            "emoji_order": "2130"
        },
        {
            "name": "information_source",
            "unicode": "2139",
            "shortname": ":information_source:",
            "code_decimal": "&#8505;",
            "category": "s",
            "emoji_order": "2131"
        },
        {
            "name": "id",
            "unicode": "1f194",
            "shortname": ":id:",
            "code_decimal": "&#127380;",
            "category": "s",
            "emoji_order": "2132"
        },
        {
            "name": "m",
            "unicode": "24c2",
            "shortname": ":m:",
            "code_decimal": "&#9410;",
            "category": "s",
            "emoji_order": "2133"
        },
        {
            "name": "new",
            "unicode": "1f195",
            "shortname": ":new:",
            "code_decimal": "&#127381;",
            "category": "s",
            "emoji_order": "2134"
        },
        {
            "name": "ng",
            "unicode": "1f196",
            "shortname": ":ng:",
            "code_decimal": "&#127382;",
            "category": "s",
            "emoji_order": "2135"
        },
        {
            "name": "o2",
            "unicode": "1f17e",
            "shortname": ":o2:",
            "code_decimal": "&#127358;",
            "category": "s",
            "emoji_order": "2136"
        },
        {
            "name": "ok",
            "unicode": "1f197",
            "shortname": ":ok:",
            "code_decimal": "&#127383;",
            "category": "s",
            "emoji_order": "2137"
        },
        {
            "name": "parking",
            "unicode": "1f17f",
            "shortname": ":parking:",
            "code_decimal": "&#127359;",
            "category": "s",
            "emoji_order": "2138"
        },
        {
            "name": "sos",
            "unicode": "1f198",
            "shortname": ":sos:",
            "code_decimal": "&#127384;",
            "category": "s",
            "emoji_order": "2139"
        },
        {
            "name": "up",
            "unicode": "1f199",
            "shortname": ":up:",
            "code_decimal": "&#127385;",
            "category": "s",
            "emoji_order": "2140"
        },
        {
            "name": "vs",
            "unicode": "1f19a",
            "shortname": ":vs:",
            "code_decimal": "&#127386;",
            "category": "s",
            "emoji_order": "2141"
        },
        {
            "name": "koko",
            "unicode": "1f201",
            "shortname": ":koko:",
            "code_decimal": "&#127489;",
            "category": "s",
            "emoji_order": "2142"
        },
        {
            "name": "sa",
            "unicode": "1f202",
            "shortname": ":sa:",
            "code_decimal": "&#127490;",
            "category": "s",
            "emoji_order": "2143"
        },
        {
            "name": "u6708",
            "unicode": "1f237",
            "shortname": ":u6708:",
            "code_decimal": "&#127543;",
            "category": "s",
            "emoji_order": "2144"
        },
        {
            "name": "u6709",
            "unicode": "1f236",
            "shortname": ":u6709:",
            "code_decimal": "&#127542;",
            "category": "s",
            "emoji_order": "2145"
        },
        {
            "name": "u6307",
            "unicode": "1f22f",
            "shortname": ":u6307:",
            "code_decimal": "&#127535;",
            "category": "s",
            "emoji_order": "2146"
        },
        {
            "name": "ideograph_advantage",
            "unicode": "1f250",
            "shortname": ":ideograph_advantage:",
            "code_decimal": "&#127568;",
            "category": "s",
            "emoji_order": "2147"
        },
        {
            "name": "u5272",
            "unicode": "1f239",
            "shortname": ":u5272:",
            "code_decimal": "&#127545;",
            "category": "s",
            "emoji_order": "2148"
        },
        {
            "name": "u7121",
            "unicode": "1f21a",
            "shortname": ":u7121:",
            "code_decimal": "&#127514;",
            "category": "s",
            "emoji_order": "2149"
        },
        {
            "name": "u7981",
            "unicode": "1f232",
            "shortname": ":u7981:",
            "code_decimal": "&#127538;",
            "category": "s",
            "emoji_order": "2150"
        },
        {
            "name": "accept",
            "unicode": "1f251",
            "shortname": ":accept:",
            "code_decimal": "&#127569;",
            "category": "s",
            "emoji_order": "2151"
        },
        {
            "name": "u7533",
            "unicode": "1f238",
            "shortname": ":u7533:",
            "code_decimal": "&#127544;",
            "category": "s",
            "emoji_order": "2152"
        },
        {
            "name": "u5408",
            "unicode": "1f234",
            "shortname": ":u5408:",
            "code_decimal": "&#127540;",
            "category": "s",
            "emoji_order": "2153"
        },
        {
            "name": "u7a7a",
            "unicode": "1f233",
            "shortname": ":u7a7a:",
            "code_decimal": "&#127539;",
            "category": "s",
            "emoji_order": "2154"
        },
        {
            "name": "congratulations",
            "unicode": "3297",
            "shortname": ":congratulations:",
            "code_decimal": "&#12951;",
            "category": "s",
            "emoji_order": "2155"
        },
        {
            "name": "secret",
            "unicode": "3299",
            "shortname": ":secret:",
            "code_decimal": "&#12953;",
            "category": "s",
            "emoji_order": "2156"
        },
        {
            "name": "u55b6",
            "unicode": "1f23a",
            "shortname": ":u55b6:",
            "code_decimal": "&#127546;",
            "category": "s",
            "emoji_order": "2157"
        },
        {
            "name": "u6e80",
            "unicode": "1f235",
            "shortname": ":u6e80:",
            "code_decimal": "&#127541;",
            "category": "s",
            "emoji_order": "2158"
        },
        {
            "name": "black_small_square",
            "unicode": "25aa",
            "shortname": ":black_small_square:",
            "code_decimal": "&#9642;",
            "category": "s",
            "emoji_order": "2159"
        },
        {
            "name": "white_small_square",
            "unicode": "25ab",
            "shortname": ":white_small_square:",
            "code_decimal": "&#9643;",
            "category": "s",
            "emoji_order": "2160"
        },
        {
            "name": "white_medium_square",
            "unicode": "25fb",
            "shortname": ":white_medium_square:",
            "code_decimal": "&#9723;",
            "category": "s",
            "emoji_order": "2161"
        },
        {
            "name": "black_medium_square",
            "unicode": "25fc",
            "shortname": ":black_medium_square:",
            "code_decimal": "&#9724;",
            "category": "s",
            "emoji_order": "2162"
        },
        {
            "name": "white_medium_small_square",
            "unicode": "25fd",
            "shortname": ":white_medium_small_square:",
            "code_decimal": "&#9725;",
            "category": "s",
            "emoji_order": "2163"
        },
        {
            "name": "black_medium_small_square",
            "unicode": "25fe",
            "shortname": ":black_medium_small_square:",
            "code_decimal": "&#9726;",
            "category": "s",
            "emoji_order": "2164"
        },
        {
            "name": "black_large_square",
            "unicode": "2b1b",
            "shortname": ":black_large_square:",
            "code_decimal": "&#11035;",
            "category": "s",
            "emoji_order": "2165"
        },
        {
            "name": "white_large_square",
            "unicode": "2b1c",
            "shortname": ":white_large_square:",
            "code_decimal": "&#11036;",
            "category": "s",
            "emoji_order": "2166"
        },
        {
            "name": "large_orange_diamond",
            "unicode": "1f536",
            "shortname": ":large_orange_diamond:",
            "code_decimal": "&#128310;",
            "category": "s",
            "emoji_order": "2167"
        },
        {
            "name": "large_blue_diamond",
            "unicode": "1f537",
            "shortname": ":large_blue_diamond:",
            "code_decimal": "&#128311;",
            "category": "s",
            "emoji_order": "2168"
        },
        {
            "name": "small_orange_diamond",
            "unicode": "1f538",
            "shortname": ":small_orange_diamond:",
            "code_decimal": "&#128312;",
            "category": "s",
            "emoji_order": "2169"
        },
        {
            "name": "small_blue_diamond",
            "unicode": "1f539",
            "shortname": ":small_blue_diamond:",
            "code_decimal": "&#128313;",
            "category": "s",
            "emoji_order": "2170"
        },
        {
            "name": "small_red_triangle",
            "unicode": "1f53a",
            "shortname": ":small_red_triangle:",
            "code_decimal": "&#128314;",
            "category": "s",
            "emoji_order": "2171"
        },
        {
            "name": "small_red_triangle_down",
            "unicode": "1f53b",
            "shortname": ":small_red_triangle_down:",
            "code_decimal": "&#128315;",
            "category": "s",
            "emoji_order": "2172"
        },
        {
            "name": "diamond_shape_with_a_dot_inside",
            "unicode": "1f4a0",
            "shortname": ":diamond_shape_with_a_dot_inside:",
            "code_decimal": "&#128160;",
            "category": "s",
            "emoji_order": "2173"
        },
        {
            "name": "radio_button",
            "unicode": "1f518",
            "shortname": ":radio_button:",
            "code_decimal": "&#128280;",
            "category": "s",
            "emoji_order": "2174"
        },
        {
            "name": "black_square_button",
            "unicode": "1f532",
            "shortname": ":black_square_button:",
            "code_decimal": "&#128306;",
            "category": "s",
            "emoji_order": "2175"
        },
        {
            "name": "white_square_button",
            "unicode": "1f533",
            "shortname": ":white_square_button:",
            "code_decimal": "&#128307;",
            "category": "s",
            "emoji_order": "2176"
        },
        {
            "name": "white_circle",
            "unicode": "26aa",
            "shortname": ":white_circle:",
            "code_decimal": "&#9898;",
            "category": "s",
            "emoji_order": "2177"
        },
        {
            "name": "black_circle",
            "unicode": "26ab",
            "shortname": ":black_circle:",
            "code_decimal": "&#9899;",
            "category": "s",
            "emoji_order": "2178"
        },
        {
            "name": "red_circle",
            "unicode": "1f534",
            "shortname": ":red_circle:",
            "code_decimal": "&#128308;",
            "category": "s",
            "emoji_order": "2179"
        },
        {
            "name": "large_blue_circle",
            "unicode": "1f535",
            "shortname": ":blue_circle:",
            "code_decimal": "&#128309;",
            "category": "s",
            "emoji_order": "2180"
        },
        {
            "name": "checkered_flag",
            "unicode": "1f3c1",
            "shortname": ":checkered_flag:",
            "code_decimal": "&#127937;",
            "category": "t",
            "emoji_order": "2181"
        },
        {
            "name": "triangular_flag_on_post",
            "unicode": "1f6a9",
            "shortname": ":triangular_flag_on_post:",
            "code_decimal": "&#128681;",
            "category": "o",
            "emoji_order": "2182"
        },
        {
            "name": "crossed_flags",
            "unicode": "1f38c",
            "shortname": ":crossed_flags:",
            "code_decimal": "&#127884;",
            "category": "o",
            "emoji_order": "2183"
        },
        {
            "name": "waving_black_flag",
            "unicode": "1f3f4",
            "shortname": ":flag_black:",
            "code_decimal": "&#127988;",
            "category": "o",
            "emoji_order": "2184"
        },
        {
            "name": "waving_white_flag",
            "unicode": "1f3f3",
            "shortname": ":flag_white:",
            "code_decimal": "&#127987;",
            "category": "o",
            "emoji_order": "2185"
        },
        {
            "name": "flag-ac",
            "unicode": "1f1e6-1f1e8",
            "shortname": ":flag_ac:",
            "code_decimal": "&#127462;&#127464;",
            "category": "f",
            "emoji_order": "2187"
        },
        {
            "name": "flag-ad",
            "unicode": "1f1e6-1f1e9",
            "shortname": ":flag_ad:",
            "code_decimal": "&#127462;&#127465;",
            "category": "f",
            "emoji_order": "2188"
        },
        {
            "name": "flag-ae",
            "unicode": "1f1e6-1f1ea",
            "shortname": ":flag_ae:",
            "code_decimal": "&#127462;&#127466;",
            "category": "f",
            "emoji_order": "2189"
        },
        {
            "name": "flag-af",
            "unicode": "1f1e6-1f1eb",
            "shortname": ":flag_af:",
            "code_decimal": "&#127462;&#127467;",
            "category": "f",
            "emoji_order": "2190"
        },
        {
            "name": "flag-ag",
            "unicode": "1f1e6-1f1ec",
            "shortname": ":flag_ag:",
            "code_decimal": "&#127462;&#127468;",
            "category": "f",
            "emoji_order": "2191"
        },
        {
            "name": "flag-ai",
            "unicode": "1f1e6-1f1ee",
            "shortname": ":flag_ai:",
            "code_decimal": "&#127462;&#127470;",
            "category": "f",
            "emoji_order": "2192"
        },
        {
            "name": "flag-al",
            "unicode": "1f1e6-1f1f1",
            "shortname": ":flag_al:",
            "code_decimal": "&#127462;&#127473;",
            "category": "f",
            "emoji_order": "2193"
        },
        {
            "name": "flag-am",
            "unicode": "1f1e6-1f1f2",
            "shortname": ":flag_am:",
            "code_decimal": "&#127462;&#127474;",
            "category": "f",
            "emoji_order": "2194"
        },
        {
            "name": "flag-ao",
            "unicode": "1f1e6-1f1f4",
            "shortname": ":flag-ao:",
            "code_decimal": "&#127462;&#127476;",
            "category": "f",
            "emoji_order": "2195"
        },
        {
            "name": "flag-aq",
            "unicode": "1f1e6-1f1f6",
            "shortname": ":flag-aq:",
            "code_decimal": "&#127462;&#127478;",
            "category": "f",
            "emoji_order": "2196"
        },
        {
            "name": "flag-ar",
            "unicode": "1f1e6-1f1f7",
            "shortname": ":flag-ar:",
            "code_decimal": "&#127462;&#127479;",
            "category": "f",
            "emoji_order": "2197"
        },
        {
            "name": "flag-as",
            "unicode": "1f1e6-1f1f8",
            "shortname": ":flag-as:",
            "code_decimal": "&#127462;&#127480;",
            "category": "f",
            "emoji_order": "2198"
        },
        {
            "name": "flag-at",
            "unicode": "1f1e6-1f1f9",
            "shortname": ":flag-at:",
            "code_decimal": "&#127462;&#127481;",
            "category": "f",
            "emoji_order": "2199"
        },
        {
            "name": "flag-au",
            "unicode": "1f1e6-1f1fa",
            "shortname": ":flag-au:",
            "code_decimal": "&#127462;&#127482;",
            "category": "f",
            "emoji_order": "2200"
        },
        {
            "name": "flag-aw",
            "unicode": "1f1e6-1f1fc",
            "shortname": ":flag-aw:",
            "code_decimal": "&#127462;&#127484;",
            "category": "f",
            "emoji_order": "2201"
        },
        {
            "name": "flag-ax",
            "unicode": "1f1e6-1f1fd",
            "shortname": ":flag-ax:",
            "code_decimal": "&#127462;&#127485;",
            "category": "f",
            "emoji_order": "2202"
        },
        {
            "name": "flag-az",
            "unicode": "1f1e6-1f1ff",
            "shortname": ":flag-az:",
            "code_decimal": "&#127462;&#127487;",
            "category": "f",
            "emoji_order": "2203"
        },
        {
            "name": "flag-ba",
            "unicode": "1f1e7-1f1e6",
            "shortname": ":flag-ba:",
            "code_decimal": "&#127463;&#127462;",
            "category": "f",
            "emoji_order": "2204"
        },
        {
            "name": "flag-bb",
            "unicode": "1f1e7-1f1e7",
            "shortname": ":flag-bb:",
            "code_decimal": "&#127463;&#127463;",
            "category": "f",
            "emoji_order": "2205"
        },
        {
            "name": "flag-bd",
            "unicode": "1f1e7-1f1e9",
            "shortname": ":flag-bd:",
            "code_decimal": "&#127463;&#127465;",
            "category": "f",
            "emoji_order": "2206"
        },
        {
            "name": "flag-be",
            "unicode": "1f1e7-1f1ea",
            "shortname": ":flag-be:",
            "code_decimal": "&#127463;&#127466;",
            "category": "f",
            "emoji_order": "2207"
        },
        {
            "name": "flag-bf",
            "unicode": "1f1e7-1f1eb",
            "shortname": ":flag-bf:",
            "code_decimal": "&#127463;&#127467;",
            "category": "f",
            "emoji_order": "2208"
        },
        {
            "name": "flag-bg",
            "unicode": "1f1e7-1f1ec",
            "shortname": ":flag-bg:",
            "code_decimal": "&#127463;&#127468;",
            "category": "f",
            "emoji_order": "2209"
        },
        {
            "name": "flag-bh",
            "unicode": "1f1e7-1f1ed",
            "shortname": ":flag-bh:",
            "code_decimal": "&#127463;&#127469;",
            "category": "f",
            "emoji_order": "2210"
        },
        {
            "name": "flag-bi",
            "unicode": "1f1e7-1f1ee",
            "shortname": ":flag-bi:",
            "code_decimal": "&#127463;&#127470;",
            "category": "f",
            "emoji_order": "2211"
        },
        {
            "name": "flag-bj",
            "unicode": "1f1e7-1f1ef",
            "shortname": ":flag-bj:",
            "code_decimal": "&#127463;&#127471;",
            "category": "f",
            "emoji_order": "2212"
        },
        {
            "name": "flag-bl",
            "unicode": "1f1e7-1f1f1",
            "shortname": ":flag-bl:",
            "code_decimal": "&#127463;&#127473;",
            "category": "f",
            "emoji_order": "2213"
        },
        {
            "name": "flag-bm",
            "unicode": "1f1e7-1f1f2",
            "shortname": ":flag-bm:",
            "code_decimal": "&#127463;&#127474;",
            "category": "f",
            "emoji_order": "2214"
        },
        {
            "name": "flag-bn",
            "unicode": "1f1e7-1f1f3",
            "shortname": ":flag-bn:",
            "code_decimal": "&#127463;&#127475;",
            "category": "f",
            "emoji_order": "2215"
        },
        {
            "name": "flag-bo",
            "unicode": "1f1e7-1f1f4",
            "shortname": ":flag-bo:",
            "code_decimal": "&#127463;&#127476;",
            "category": "f",
            "emoji_order": "2216"
        },
        {
            "name": "flag-bq",
            "unicode": "1f1e7-1f1f6",
            "shortname": ":flag-bq:",
            "code_decimal": "&#127463;&#127478;",
            "category": "f",
            "emoji_order": "2217"
        },
        {
            "name": "flag-br",
            "unicode": "1f1e7-1f1f7",
            "shortname": ":flag-br:",
            "code_decimal": "&#127463;&#127479;",
            "category": "f",
            "emoji_order": "2218"
        },
        {
            "name": "flag-bs",
            "unicode": "1f1e7-1f1f8",
            "shortname": ":flag-bs:",
            "code_decimal": "&#127463;&#127480;",
            "category": "f",
            "emoji_order": "2219"
        },
        {
            "name": "flag-bt",
            "unicode": "1f1e7-1f1f9",
            "shortname": ":flag-bt:",
            "code_decimal": "&#127463;&#127481;",
            "category": "f",
            "emoji_order": "2220"
        },
        {
            "name": "flag-bv",
            "unicode": "1f1e7-1f1fb",
            "shortname": ":flag-bv:",
            "code_decimal": "&#127463;&#127483;",
            "category": "f",
            "emoji_order": "2221"
        },
        {
            "name": "flag-bw",
            "unicode": "1f1e7-1f1fc",
            "shortname": ":flag-bw:",
            "code_decimal": "&#127463;&#127484;",
            "category": "f",
            "emoji_order": "2222"
        },
        {
            "name": "flag-by",
            "unicode": "1f1e7-1f1fe",
            "shortname": ":flag-by:",
            "code_decimal": "&#127463;&#127486;",
            "category": "f",
            "emoji_order": "2223"
        },
        {
            "name": "flag-bz",
            "unicode": "1f1e7-1f1ff",
            "shortname": ":flag-bz:",
            "code_decimal": "&#127463;&#127487;",
            "category": "f",
            "emoji_order": "2224"
        },
        {
            "name": "flag-ca",
            "unicode": "1f1e8-1f1e6",
            "shortname": ":flag-ca:",
            "code_decimal": "&#127464;&#127462;",
            "category": "f",
            "emoji_order": "2225"
        },
        {
            "name": "flag-cc",
            "unicode": "1f1e8-1f1e8",
            "shortname": ":flag-cc:",
            "code_decimal": "&#127464;&#127464;",
            "category": "f",
            "emoji_order": "2226"
        },
        {
            "name": "flag-cd",
            "unicode": "1f1e8-1f1e9",
            "shortname": ":flag-cd:",
            "code_decimal": "&#127464;&#127465;",
            "category": "f",
            "emoji_order": "2227"
        },
        {
            "name": "flag-cf",
            "unicode": "1f1e8-1f1eb",
            "shortname": ":flag-cf:",
            "code_decimal": "&#127464;&#127467;",
            "category": "f",
            "emoji_order": "2228"
        },
        {
            "name": "flag-cg",
            "unicode": "1f1e8-1f1ec",
            "shortname": ":flag-cg:",
            "code_decimal": "&#127464;&#127468;",
            "category": "f",
            "emoji_order": "2229"
        },
        {
            "name": "flag-ch",
            "unicode": "1f1e8-1f1ed",
            "shortname": ":flag-ch:",
            "code_decimal": "&#127464;&#127469;",
            "category": "f",
            "emoji_order": "2230"
        },
        {
            "name": "flag-ci",
            "unicode": "1f1e8-1f1ee",
            "shortname": ":flag-ci:",
            "code_decimal": "&#127464;&#127470;",
            "category": "f",
            "emoji_order": "2231"
        },
        {
            "name": "flag-ck",
            "unicode": "1f1e8-1f1f0",
            "shortname": ":flag-ck:",
            "code_decimal": "&#127464;&#127472;",
            "category": "f",
            "emoji_order": "2232"
        },
        {
            "name": "flag-cl",
            "unicode": "1f1e8-1f1f1",
            "shortname": ":flag-cl:",
            "code_decimal": "&#127464;&#127473;",
            "category": "f",
            "emoji_order": "2233"
        },
        {
            "name": "flag-cm",
            "unicode": "1f1e8-1f1f2",
            "shortname": ":flag-cm:",
            "code_decimal": "&#127464;&#127474;",
            "category": "f",
            "emoji_order": "2234"
        },
        {
            "name": "flag-cn",
            "unicode": "1f1e8-1f1f3",
            "shortname": ":flag-cn:",
            "code_decimal": "&#127464;&#127475;",
            "category": "f",
            "emoji_order": "2235"
        },
        {
            "name": "flag-co",
            "unicode": "1f1e8-1f1f4",
            "shortname": ":flag-co:",
            "code_decimal": "&#127464;&#127476;",
            "category": "f",
            "emoji_order": "2236"
        },
        {
            "name": "flag-cp",
            "unicode": "1f1e8-1f1f5",
            "shortname": ":flag-cp:",
            "code_decimal": "&#127464;&#127477;",
            "category": "f",
            "emoji_order": "2237"
        },
        {
            "name": "flag-cr",
            "unicode": "1f1e8-1f1f7",
            "shortname": ":flag-cr:",
            "code_decimal": "&#127464;&#127479;",
            "category": "f",
            "emoji_order": "2238"
        },
        {
            "name": "flag-cu",
            "unicode": "1f1e8-1f1fa",
            "shortname": ":flag-cu:",
            "code_decimal": "&#127464;&#127482;",
            "category": "f",
            "emoji_order": "2239"
        },
        {
            "name": "flag-cv",
            "unicode": "1f1e8-1f1fb",
            "shortname": ":flag-cv:",
            "code_decimal": "&#127464;&#127483;",
            "category": "f",
            "emoji_order": "2240"
        },
        {
            "name": "flag-cw",
            "unicode": "1f1e8-1f1fc",
            "shortname": ":flag-cw:",
            "code_decimal": "&#127464;&#127484;",
            "category": "f",
            "emoji_order": "2241"
        },
        {
            "name": "flag-cx",
            "unicode": "1f1e8-1f1fd",
            "shortname": ":flag-cx:",
            "code_decimal": "&#127464;&#127485;",
            "category": "f",
            "emoji_order": "2242"
        },
        {
            "name": "flag-cy",
            "unicode": "1f1e8-1f1fe",
            "shortname": ":flag-cy:",
            "code_decimal": "&#127464;&#127486;",
            "category": "f",
            "emoji_order": "2243"
        },
        {
            "name": "flag-cz",
            "unicode": "1f1e8-1f1ff",
            "shortname": ":flag-cz:",
            "code_decimal": "&#127464;&#127487;",
            "category": "f",
            "emoji_order": "2244"
        },
        {
            "name": "flag-de",
            "unicode": "1f1e9-1f1ea",
            "shortname": ":flag-de:",
            "code_decimal": "&#127465;&#127466;",
            "category": "f",
            "emoji_order": "2245"
        },
        {
            "name": "flag-dg",
            "unicode": "1f1e9-1f1ec",
            "shortname": ":flag-dg:",
            "code_decimal": "&#127465;&#127468;",
            "category": "f",
            "emoji_order": "2246"
        },
        {
            "name": "flag-dj",
            "unicode": "1f1e9-1f1ef",
            "shortname": ":flag-dj:",
            "code_decimal": "&#127465;&#127471;",
            "category": "f",
            "emoji_order": "2247"
        },
        {
            "name": "flag-dk",
            "unicode": "1f1e9-1f1f0",
            "shortname": ":flag-dk:",
            "code_decimal": "&#127465;&#127472;",
            "category": "f",
            "emoji_order": "2248"
        },
        {
            "name": "flag-dm",
            "unicode": "1f1e9-1f1f2",
            "shortname": ":flag-dm:",
            "code_decimal": "&#127465;&#127474;",
            "category": "f",
            "emoji_order": "2249"
        },
        {
            "name": "flag-do",
            "unicode": "1f1e9-1f1f4",
            "shortname": ":flag-do:",
            "code_decimal": "&#127465;&#127476;",
            "category": "f",
            "emoji_order": "2250"
        },
        {
            "name": "flag-dz",
            "unicode": "1f1e9-1f1ff",
            "shortname": ":flag-dz:",
            "code_decimal": "&#127465;&#127487;",
            "category": "f",
            "emoji_order": "2251"
        },
        {
            "name": "flag-ea",
            "unicode": "1f1ea-1f1e6",
            "shortname": ":flag-ea:",
            "code_decimal": "&#127466;&#127462;",
            "category": "f",
            "emoji_order": "2252"
        },
        {
            "name": "flag-ec",
            "unicode": "1f1ea-1f1e8",
            "shortname": ":flag-ec:",
            "code_decimal": "&#127466;&#127464;",
            "category": "f",
            "emoji_order": "2253"
        },
        {
            "name": "flag-ee",
            "unicode": "1f1ea-1f1ea",
            "shortname": ":flag-ee:",
            "code_decimal": "&#127466;&#127466;",
            "category": "f",
            "emoji_order": "2254"
        },
        {
            "name": "flag-eg",
            "unicode": "1f1ea-1f1ec",
            "shortname": ":flag-eg:",
            "code_decimal": "&#127466;&#127468;",
            "category": "f",
            "emoji_order": "2255"
        },
        {
            "name": "flag-eh",
            "unicode": "1f1ea-1f1ed",
            "shortname": ":flag-eh:",
            "code_decimal": "&#127466;&#127469;",
            "category": "f",
            "emoji_order": "2256"
        },
        {
            "name": "flag-er",
            "unicode": "1f1ea-1f1f7",
            "shortname": ":flag-er:",
            "code_decimal": "&#127466;&#127479;",
            "category": "f",
            "emoji_order": "2257"
        },
        {
            "name": "flag-es",
            "unicode": "1f1ea-1f1f8",
            "shortname": ":flag-es:",
            "code_decimal": "&#127466;&#127480;",
            "category": "f",
            "emoji_order": "2258"
        },
        {
            "name": "flag-et",
            "unicode": "1f1ea-1f1f9",
            "shortname": ":flag-et:",
            "code_decimal": "&#127466;&#127481;",
            "category": "f",
            "emoji_order": "2259"
        },
        {
            "name": "flag-eu",
            "unicode": "1f1ea-1f1fa",
            "shortname": ":flag-eu:",
            "code_decimal": "&#127466;&#127482;",
            "category": "f",
            "emoji_order": "2260"
        },
        {
            "name": "flag-fi",
            "unicode": "1f1eb-1f1ee",
            "shortname": ":flag-fi:",
            "code_decimal": "&#127467;&#127470;",
            "category": "f",
            "emoji_order": "2261"
        },
        {
            "name": "flag-fj",
            "unicode": "1f1eb-1f1ef",
            "shortname": ":flag-fj:",
            "code_decimal": "&#127467;&#127471;",
            "category": "f",
            "emoji_order": "2262"
        },
        {
            "name": "flag-fk",
            "unicode": "1f1eb-1f1f0",
            "shortname": ":flag-fk:",
            "code_decimal": "&#127467;&#127472;",
            "category": "f",
            "emoji_order": "2263"
        },
        {
            "name": "flag-fm",
            "unicode": "1f1eb-1f1f2",
            "shortname": ":flag-fm:",
            "code_decimal": "&#127467;&#127474;",
            "category": "f",
            "emoji_order": "2264"
        },
        {
            "name": "flag-fo",
            "unicode": "1f1eb-1f1f4",
            "shortname": ":flag-fo:",
            "code_decimal": "&#127467;&#127476;",
            "category": "f",
            "emoji_order": "2265"
        },
        {
            "name": "flag-fr",
            "unicode": "1f1eb-1f1f7",
            "shortname": ":flag-fr:",
            "code_decimal": "&#127467;&#127479;",
            "category": "f",
            "emoji_order": "2266"
        },
        {
            "name": "flag-ga",
            "unicode": "1f1ec-1f1e6",
            "shortname": ":flag-ga:",
            "code_decimal": "&#127468;&#127462;",
            "category": "f",
            "emoji_order": "2267"
        },
        {
            "name": "flag-gb",
            "unicode": "1f1ec-1f1e7",
            "shortname": ":flag-gb:",
            "code_decimal": "&#127468;&#127463;",
            "category": "f",
            "emoji_order": "2268"
        },
        {
            "name": "flag-gd",
            "unicode": "1f1ec-1f1e9",
            "shortname": ":flag-gd:",
            "code_decimal": "&#127468;&#127465;",
            "category": "f",
            "emoji_order": "2269"
        },
        {
            "name": "flag-ge",
            "unicode": "1f1ec-1f1ea",
            "shortname": ":flag-ge:",
            "code_decimal": "&#127468;&#127466;",
            "category": "f",
            "emoji_order": "2270"
        },
        {
            "name": "flag-gf",
            "unicode": "1f1ec-1f1eb",
            "shortname": ":flag-gf:",
            "code_decimal": "&#127468;&#127467;",
            "category": "f",
            "emoji_order": "2271"
        },
        {
            "name": "flag-gg",
            "unicode": "1f1ec-1f1ec",
            "shortname": ":flag-gg:",
            "code_decimal": "&#127468;&#127468;",
            "category": "f",
            "emoji_order": "2272"
        },
        {
            "name": "flag-gh",
            "unicode": "1f1ec-1f1ed",
            "shortname": ":flag-gh:",
            "code_decimal": "&#127468;&#127469;",
            "category": "f",
            "emoji_order": "2273"
        },
        {
            "name": "flag-gi",
            "unicode": "1f1ec-1f1ee",
            "shortname": ":flag-gi:",
            "code_decimal": "&#127468;&#127470;",
            "category": "f",
            "emoji_order": "2274"
        },
        {
            "name": "flag-gl",
            "unicode": "1f1ec-1f1f1",
            "shortname": ":flag-gl:",
            "code_decimal": "&#127468;&#127473;",
            "category": "f",
            "emoji_order": "2275"
        },
        {
            "name": "flag-gm",
            "unicode": "1f1ec-1f1f2",
            "shortname": ":flag-gm:",
            "code_decimal": "&#127468;&#127474;",
            "category": "f",
            "emoji_order": "2276"
        },
        {
            "name": "flag-gn",
            "unicode": "1f1ec-1f1f3",
            "shortname": ":flag-gn:",
            "code_decimal": "&#127468;&#127475;",
            "category": "f",
            "emoji_order": "2277"
        },
        {
            "name": "flag-gp",
            "unicode": "1f1ec-1f1f5",
            "shortname": ":flag-gp:",
            "code_decimal": "&#127468;&#127477;",
            "category": "f",
            "emoji_order": "2278"
        },
        {
            "name": "flag-gq",
            "unicode": "1f1ec-1f1f6",
            "shortname": ":flag-gq:",
            "code_decimal": "&#127468;&#127478;",
            "category": "f",
            "emoji_order": "2279"
        },
        {
            "name": "flag-gr",
            "unicode": "1f1ec-1f1f7",
            "shortname": ":flag-gr:",
            "code_decimal": "&#127468;&#127479;",
            "category": "f",
            "emoji_order": "2280"
        },
        {
            "name": "flag-gs",
            "unicode": "1f1ec-1f1f8",
            "shortname": ":flag-gs:",
            "code_decimal": "&#127468;&#127480;",
            "category": "f",
            "emoji_order": "2281"
        },
        {
            "name": "flag-gt",
            "unicode": "1f1ec-1f1f9",
            "shortname": ":flag-gt:",
            "code_decimal": "&#127468;&#127481;",
            "category": "f",
            "emoji_order": "2282"
        },
        {
            "name": "flag-gu",
            "unicode": "1f1ec-1f1fa",
            "shortname": ":flag-gu:",
            "code_decimal": "&#127468;&#127482;",
            "category": "f",
            "emoji_order": "2283"
        },
        {
            "name": "flag-gw",
            "unicode": "1f1ec-1f1fc",
            "shortname": ":flag-gw:",
            "code_decimal": "&#127468;&#127484;",
            "category": "f",
            "emoji_order": "2284"
        },
        {
            "name": "flag-gy",
            "unicode": "1f1ec-1f1fe",
            "shortname": ":flag-gy:",
            "code_decimal": "&#127468;&#127486;",
            "category": "f",
            "emoji_order": "2285"
        },
        {
            "name": "flag-hk",
            "unicode": "1f1ed-1f1f0",
            "shortname": ":flag-hk:",
            "code_decimal": "&#127469;&#127472;",
            "category": "f",
            "emoji_order": "2286"
        },
        {
            "name": "flag-hm",
            "unicode": "1f1ed-1f1f2",
            "shortname": ":flag-hm:",
            "code_decimal": "&#127469;&#127474;",
            "category": "f",
            "emoji_order": "2287"
        },
        {
            "name": "flag-hn",
            "unicode": "1f1ed-1f1f3",
            "shortname": ":flag-hn:",
            "code_decimal": "&#127469;&#127475;",
            "category": "f",
            "emoji_order": "2288"
        },
        {
            "name": "flag-hr",
            "unicode": "1f1ed-1f1f7",
            "shortname": ":flag-hr:",
            "code_decimal": "&#127469;&#127479;",
            "category": "f",
            "emoji_order": "2289"
        },
        {
            "name": "flag-ht",
            "unicode": "1f1ed-1f1f9",
            "shortname": ":flag-ht:",
            "code_decimal": "&#127469;&#127481;",
            "category": "f",
            "emoji_order": "2290"
        },
        {
            "name": "flag-hu",
            "unicode": "1f1ed-1f1fa",
            "shortname": ":flag-hu:",
            "code_decimal": "&#127469;&#127482;",
            "category": "f",
            "emoji_order": "2291"
        },
        {
            "name": "flag-ic",
            "unicode": "1f1ee-1f1e8",
            "shortname": ":flag-ic:",
            "code_decimal": "&#127470;&#127464;",
            "category": "f",
            "emoji_order": "2292"
        },
        {
            "name": "flag-id",
            "unicode": "1f1ee-1f1e9",
            "shortname": ":flag-id:",
            "code_decimal": "&#127470;&#127465;",
            "category": "f",
            "emoji_order": "2293"
        },
        {
            "name": "flag-ie",
            "unicode": "1f1ee-1f1ea",
            "shortname": ":flag-ie:",
            "code_decimal": "&#127470;&#127466;",
            "category": "f",
            "emoji_order": "2294"
        },
        {
            "name": "flag-il",
            "unicode": "1f1ee-1f1f1",
            "shortname": ":flag-il:",
            "code_decimal": "&#127470;&#127473;",
            "category": "f",
            "emoji_order": "2295"
        },


        {
            "name": "flag-im",
            "unicode": "1f1ee-1f1f2",
            "shortname": ":flag-im:",
            "code_decimal": "&#127470;&#127474;",
            "category": "f",
            "emoji_order": "2296"
        },
        {
            "name": "flag-in",
            "unicode": "1f1ee-1f1f3",
            "shortname": ":flag-in:",
            "code_decimal": "&#127470;&#127475;",
            "category": "f",
            "emoji_order": "2297"
        },
        {
            "name": "flag-io",
            "unicode": "1f1ee-1f1f4",
            "shortname": ":flag-io:",
            "code_decimal": "&#127470;&#127476;",
            "category": "f",
            "emoji_order": "2298"
        },
        {
            "name": "flag-iq",
            "unicode": "1f1ee-1f1f6",
            "shortname": ":flag-iq:",
            "code_decimal": "&#127470;&#127478;",
            "category": "f",
            "emoji_order": "2299"
        },
        {
            "name": "flag-ir",
            "unicode": "1f1ee-1f1f7",
            "shortname": ":flag-ir:",
            "code_decimal": "&#127470;&#127479;",
            "category": "f",
            "emoji_order": "2300"
        },
        {
            "name": "flag-is",
            "unicode": "1f1ee-1f1f8",
            "shortname": ":flag-is:",
            "code_decimal": "&#127470;&#127480;",
            "category": "f",
            "emoji_order": "2301"
        },
        {
            "name": "flag-it",
            "unicode": "1f1ee-1f1f9",
            "shortname": ":flag-it:",
            "code_decimal": "&#127470;&#127481;",
            "category": "f",
            "emoji_order": "2302"
        },
        {
            "name": "flag-je",
            "unicode": "1f1ef-1f1ea",
            "shortname": ":flag-je:",
            "code_decimal": "&#127471;&#127466;",
            "category": "f",
            "emoji_order": "2303"
        },
        {
            "name": "flag-jm",
            "unicode": "1f1ef-1f1f2",
            "shortname": ":flag-jm:",
            "code_decimal": "&#127471;&#127474;",
            "category": "f",
            "emoji_order": "2304"
        },
        {
            "name": "flag-jo",
            "unicode": "1f1ef-1f1f4",
            "shortname": ":flag-jo:",
            "code_decimal": "&#127471;&#127476;",
            "category": "f",
            "emoji_order": "2305"
        },
        {
            "name": "flag-jp",
            "unicode": "1f1ef-1f1f5",
            "shortname": ":flag-jp:",
            "code_decimal": "&#127471;&#127477;",
            "category": "f",
            "emoji_order": "2306"
        },
        {
            "name": "flag-ke",
            "unicode": "1f1f0-1f1ea",
            "shortname": ":flag-ke:",
            "code_decimal": "&#127472;&#127466;",
            "category": "f",
            "emoji_order": "2307"
        },
        {
            "name": "flag-kg",
            "unicode": "1f1f0-1f1ec",
            "shortname": ":flag-kg:",
            "code_decimal": "&#127472;&#127468;",
            "category": "f",
            "emoji_order": "2308"
        },
        {
            "name": "flag-kh",
            "unicode": "1f1f0-1f1ed",
            "shortname": ":flag-kh:",
            "code_decimal": "&#127472;&#127469;",
            "category": "f",
            "emoji_order": "2309"
        },
        {
            "name": "flag-ki",
            "unicode": "1f1f0-1f1ee",
            "shortname": ":flag-ki:",
            "code_decimal": "&#127472;&#127470;",
            "category": "f",
            "emoji_order": "2310"
        },
        {
            "name": "flag-km",
            "unicode": "1f1f0-1f1f2",
            "shortname": ":flag-km:",
            "code_decimal": "&#127472;&#127474;",
            "category": "f",
            "emoji_order": "2311"
        },
        {
            "name": "flag-kn",
            "unicode": "1f1f0-1f1f3",
            "shortname": ":flag-kn:",
            "code_decimal": "&#127472;&#127475;",
            "category": "f",
            "emoji_order": "2312"
        },
        {
            "name": "flag-kp",
            "unicode": "1f1f0-1f1f5",
            "shortname": ":flag-kp:",
            "code_decimal": "&#127472;&#127477;",
            "category": "f",
            "emoji_order": "2313"
        },
        {
            "name": "flag-kr",
            "unicode": "1f1f0-1f1f7",
            "shortname": ":flag-kr:",
            "code_decimal": "&#127472;&#127479;",
            "category": "f",
            "emoji_order": "2314"
        },
        {
            "name": "flag-kw",
            "unicode": "1f1f0-1f1fc",
            "shortname": ":flag-kw:",
            "code_decimal": "&#127472;&#127484;",
            "category": "f",
            "emoji_order": "2315"
        },
        {
            "name": "flag-ky",
            "unicode": "1f1f0-1f1fe",
            "shortname": ":flag-ky:",
            "code_decimal": "&#127472;&#127486;",
            "category": "f",
            "emoji_order": "2316"
        },
        {
            "name": "flag-kz",
            "unicode": "1f1f0-1f1ff",
            "shortname": ":flag-kz:",
            "code_decimal": "&#127472;&#127487;",
            "category": "f",
            "emoji_order": "2317"
        },
        {
            "name": "flag-la",
            "unicode": "1f1f1-1f1e6",
            "shortname": ":flag-la:",
            "code_decimal": "&#127473;&#127462;",
            "category": "f",
            "emoji_order": "2318"
        },
        {
            "name": "flag-lb",
            "unicode": "1f1f1-1f1e7",
            "shortname": ":flag-lb:",
            "code_decimal": "&#127473;&#127463;",
            "category": "f",
            "emoji_order": "2319"
        },
        {
            "name": "flag-lc",
            "unicode": "1f1f1-1f1e8",
            "shortname": ":flag-lc:",
            "code_decimal": "&#127473;&#127464;",
            "category": "f",
            "emoji_order": "2320"
        },
        {
            "name": "flag-li",
            "unicode": "1f1f1-1f1ee",
            "shortname": ":flag-li:",
            "code_decimal": "&#127473;&#127470;",
            "category": "f",
            "emoji_order": "2321"
        },
        {
            "name": "flag-lk",
            "unicode": "1f1f1-1f1f0",
            "shortname": ":flag-lk:",
            "code_decimal": "&#127473;&#127472;",
            "category": "f",
            "emoji_order": "2322"
        },
        {
            "name": "flag-lr",
            "unicode": "1f1f1-1f1f7",
            "shortname": ":flag-lr:",
            "code_decimal": "&#127473;&#127479;",
            "category": "f",
            "emoji_order": "2323"
        },
        {
            "name": "flag-ls",
            "unicode": "1f1f1-1f1f8",
            "shortname": ":flag-ls:",
            "code_decimal": "&#127473;&#127480;",
            "category": "f",
            "emoji_order": "2324"
        },
        {
            "name": "flag-lt",
            "unicode": "1f1f1-1f1f9",
            "shortname": ":flag-lt:",
            "code_decimal": "&#127473;&#127481;",
            "category": "f",
            "emoji_order": "2325"
        },
        {
            "name": "flag-lu",
            "unicode": "1f1f1-1f1fa",
            "shortname": ":flag-lu:",
            "code_decimal": "&#127473;&#127482;",
            "category": "f",
            "emoji_order": "2326"
        },
        {
            "name": "flag-lv",
            "unicode": "1f1f1-1f1fb",
            "shortname": ":flag-lv:",
            "code_decimal": "&#127473;&#127483;",
            "category": "f",
            "emoji_order": "2327"
        },
        {
            "name": "flag-ly",
            "unicode": "1f1f1-1f1fe",
            "shortname": ":flag-ly:",
            "code_decimal": "&#127473;&#127486;",
            "category": "f",
            "emoji_order": "2328"
        },
        {
            "name": "flag-ma",
            "unicode": "1f1f2-1f1e6",
            "shortname": ":flag-ma:",
            "code_decimal": "&#127474;&#127462;",
            "category": "f",
            "emoji_order": "2329"
        },
        {
            "name": "flag-mc",
            "unicode": "1f1f2-1f1e8",
            "shortname": ":flag-mc:",
            "code_decimal": "&#127474;&#127464;",
            "category": "f",
            "emoji_order": "2330"
        },
        {
            "name": "flag-md",
            "unicode": "1f1f2-1f1e9",
            "shortname": ":flag-md:",
            "code_decimal": "&#127474;&#127465;",
            "category": "f",
            "emoji_order": "2331"
        },
        {
            "name": "flag-me",
            "unicode": "1f1f2-1f1ea",
            "shortname": ":flag-me:",
            "code_decimal": "&#127474;&#127466;",
            "category": "f",
            "emoji_order": "2332"
        },
        {
            "name": "flag-mf",
            "unicode": "1f1f2-1f1eb",
            "shortname": ":flag-mf:",
            "code_decimal": "&#127474;&#127467;",
            "category": "f",
            "emoji_order": "2333"
        },
        {
            "name": "flag-mg",
            "unicode": "1f1f2-1f1ec",
            "shortname": ":flag-mg:",
            "code_decimal": "&#127474;&#127468;",
            "category": "f",
            "emoji_order": "2334"
        },
        {
            "name": "flag-mh",
            "unicode": "1f1f2-1f1ed",
            "shortname": ":flag-mh:",
            "code_decimal": "&#127474;&#127469;",
            "category": "f",
            "emoji_order": "2335"
        },
        {
            "name": "flag-mk",
            "unicode": "1f1f2-1f1f0",
            "shortname": ":flag-mk:",
            "code_decimal": "&#127474;&#127472;",
            "category": "f",
            "emoji_order": "2336"
        },
        {
            "name": "flag-ml",
            "unicode": "1f1f2-1f1f1",
            "shortname": ":flag-ml:",
            "code_decimal": "&#127474;&#127473;",
            "category": "f",
            "emoji_order": "2337"
        },
        {
            "name": "flag-mm",
            "unicode": "1f1f2-1f1f2",
            "shortname": ":flag-mm:",
            "code_decimal": "&#127474;&#127474;",
            "category": "f",
            "emoji_order": "2338"
        },
        {
            "name": "flag-mn",
            "unicode": "1f1f2-1f1f3",
            "shortname": ":flag-mn:",
            "code_decimal": "&#127474;&#127475;",
            "category": "f",
            "emoji_order": "2339"
        },
        {
            "name": "flag-mo",
            "unicode": "1f1f2-1f1f4",
            "shortname": ":flag-mo:",
            "code_decimal": "&#127474;&#127476;",
            "category": "f",
            "emoji_order": "2340"
        },
        {
            "name": "flag-mp",
            "unicode": "1f1f2-1f1f5",
            "shortname": ":flag-mp:",
            "code_decimal": "&#127474;&#127477;",
            "category": "f",
            "emoji_order": "2341"
        },
        {
            "name": "flag-mq",
            "unicode": "1f1f2-1f1f6",
            "shortname": ":flag-mq:",
            "code_decimal": "&#127474;&#127478;",
            "category": "f",
            "emoji_order": "2342"
        },
        {
            "name": "flag-mr",
            "unicode": "1f1f2-1f1f7",
            "shortname": ":flag-mr:",
            "code_decimal": "&#127474;&#127479;",
            "category": "f",
            "emoji_order": "2343"
        },
        {
            "name": "flag-ms",
            "unicode": "1f1f2-1f1f8",
            "shortname": ":flag-ms:",
            "code_decimal": "&#127474;&#127480;",
            "category": "f",
            "emoji_order": "2344"
        },
        {
            "name": "flag-mt",
            "unicode": "1f1f2-1f1f9",
            "shortname": ":flag-mt:",
            "code_decimal": "&#127474;&#127481;",
            "category": "f",
            "emoji_order": "2345"
        },
        {
            "name": "flag-mu",
            "unicode": "1f1f2-1f1fa",
            "shortname": ":flag-mu:",
            "code_decimal": "&#127474;&#127482;",
            "category": "f",
            "emoji_order": "2346"
        },
        {
            "name": "flag-mv",
            "unicode": "1f1f2-1f1fb",
            "shortname": ":flag-mv:",
            "code_decimal": "&#127474;&#127483;",
            "category": "f",
            "emoji_order": "2347"
        },
        {
            "name": "flag-mw",
            "unicode": "1f1f2-1f1fc",
            "shortname": ":flag-mw:",
            "code_decimal": "&#127474;&#127484;",
            "category": "f",
            "emoji_order": "2348"
        },
        {
            "name": "flag-mx",
            "unicode": "1f1f2-1f1fd",
            "shortname": ":flag-mx:",
            "code_decimal": "&#127474;&#127485;",
            "category": "f",
            "emoji_order": "2349"
        },
        {
            "name": "flag-my",
            "unicode": "1f1f2-1f1fe",
            "shortname": ":flag-my:",
            "code_decimal": "&#127474;&#127486;",
            "category": "f",
            "emoji_order": "2350"
        },
        {
            "name": "flag-mz",
            "unicode": "1f1f2-1f1ff",
            "shortname": ":flag-mz:",
            "code_decimal": "&#127474;&#127487;",
            "category": "f",
            "emoji_order": "2351"
        },
        {
            "name": "flag-na",
            "unicode": "1f1f3-1f1e6",
            "shortname": ":flag-na:",
            "code_decimal": "&#127475;&#127462;",
            "category": "f",
            "emoji_order": "2352"
        },
        {
            "name": "flag-nc",
            "unicode": "1f1f3-1f1e8",
            "shortname": ":flag-nc:",
            "code_decimal": "&#127475;&#127464;",
            "category": "f",
            "emoji_order": "2353"
        },
        {
            "name": "flag-ne",
            "unicode": "1f1f3-1f1ea",
            "shortname": ":flag-ne:",
            "code_decimal": "&#127475;&#127466;",
            "category": "f",
            "emoji_order": "2354"
        },
        {
            "name": "flag-nf",
            "unicode": "1f1f3-1f1eb",
            "shortname": ":flag-nf:",
            "code_decimal": "&#127475;&#127467;",
            "category": "f",
            "emoji_order": "2355"
        },
        {
            "name": "flag-ng",
            "unicode": "1f1f3-1f1ec",
            "shortname": ":flag-ng:",
            "code_decimal": "&#127475;&#127468;",
            "category": "f",
            "emoji_order": "2356"
        },
        {
            "name": "flag-ni",
            "unicode": "1f1f3-1f1ee",
            "shortname": ":flag-ni:",
            "code_decimal": "&#127475;&#127470;",
            "category": "f",
            "emoji_order": "2357"
        },
        {
            "name": "flag-nl",
            "unicode": "1f1f3-1f1f1",
            "shortname": ":flag-nl:",
            "code_decimal": "&#127475;&#127473;",
            "category": "f",
            "emoji_order": "2358"
        },
        {
            "name": "flag-no",
            "unicode": "1f1f3-1f1f4",
            "shortname": ":flag-no:",
            "code_decimal": "&#127475;&#127476;",
            "category": "f",
            "emoji_order": "2359"
        },
        {
            "name": "flag-np",
            "unicode": "1f1f3-1f1f5",
            "shortname": ":flag-np:",
            "code_decimal": "&#127475;&#127477;",
            "category": "f",
            "emoji_order": "2360"
        },
        {
            "name": "flag-nr",
            "unicode": "1f1f3-1f1f7",
            "shortname": ":flag-nr:",
            "code_decimal": "&#127475;&#127479;",
            "category": "f",
            "emoji_order": "2361"
        },
        {
            "name": "flag-nu",
            "unicode": "1f1f3-1f1fa",
            "shortname": ":flag-nu:",
            "code_decimal": "&#127475;&#127482;",
            "category": "f",
            "emoji_order": "2362"
        },
        {
            "name": "flag-nz",
            "unicode": "1f1f3-1f1ff",
            "shortname": ":flag-nz:",
            "code_decimal": "&#127475;&#127487;",
            "category": "f",
            "emoji_order": "2363"
        },
        {
            "name": "flag-om",
            "unicode": "1f1f4-1f1f2",
            "shortname": ":flag-om:",
            "code_decimal": "&#127476;&#127474;",
            "category": "f",
            "emoji_order": "2364"
        },
        {
            "name": "flag-pa",
            "unicode": "1f1f5-1f1e6",
            "shortname": ":flag-pa:",
            "code_decimal": "&#127477;&#127462;",
            "category": "f",
            "emoji_order": "2365"
        },
        {
            "name": "flag-pe",
            "unicode": "1f1f5-1f1ea",
            "shortname": ":flag-pe:",
            "code_decimal": "&#127477;&#127466;",
            "category": "f",
            "emoji_order": "2366"
        },
        {
            "name": "flag-pf",
            "unicode": "1f1f5-1f1eb",
            "shortname": ":flag-pf:",
            "code_decimal": "&#127477;&#127467;",
            "category": "f",
            "emoji_order": "2367"
        },
        {
            "name": "flag-pg",
            "unicode": "1f1f5-1f1ec",
            "shortname": ":flag-pg:",
            "code_decimal": "&#127477;&#127468;",
            "category": "f",
            "emoji_order": "2368"
        },
        {
            "name": "flag-ph",
            "unicode": "1f1f5-1f1ed",
            "shortname": ":flag-ph:",
            "code_decimal": "&#127477;&#127469;",
            "category": "f",
            "emoji_order": "2369"
        },
        {
            "name": "flag-pk",
            "unicode": "1f1f5-1f1f0",
            "shortname": ":flag-pk:",
            "code_decimal": "&#127477;&#127472;",
            "category": "f",
            "emoji_order": "2370"
        },
        {
            "name": "flag-pl",
            "unicode": "1f1f5-1f1f1",
            "shortname": ":flag-pl:",
            "code_decimal": "&#127477;&#127473;",
            "category": "f",
            "emoji_order": "2371"
        },
        {
            "name": "flag-pm",
            "unicode": "1f1f5-1f1f2",
            "shortname": ":flag-pm:",
            "code_decimal": "&#127477;&#127474;",
            "category": "f",
            "emoji_order": "2372"
        },
        {
            "name": "flag-pn",
            "unicode": "1f1f5-1f1f3",
            "shortname": ":flag-pn:",
            "code_decimal": "&#127477;&#127475;",
            "category": "f",
            "emoji_order": "2373"
        },
        {
            "name": "flag-pr",
            "unicode": "1f1f5-1f1f7",
            "shortname": ":flag-pr:",
            "code_decimal": "&#127477;&#127479;",
            "category": "f",
            "emoji_order": "2374"
        },
        {
            "name": "flag-ps",
            "unicode": "1f1f5-1f1f8",
            "shortname": ":flag-ps:",
            "code_decimal": "&#127477;&#127480;",
            "category": "f",
            "emoji_order": "2375"
        },
        {
            "name": "flag-pt",
            "unicode": "1f1f5-1f1f9",
            "shortname": ":flag-pt:",
            "code_decimal": "&#127477;&#127481;",
            "category": "f",
            "emoji_order": "2376"
        },
        {
            "name": "flag-pw",
            "unicode": "1f1f5-1f1fc",
            "shortname": ":flag-pw:",
            "code_decimal": "&#127477;&#127484;",
            "category": "f",
            "emoji_order": "2377"
        },
        {
            "name": "flag-py",
            "unicode": "1f1f5-1f1fe",
            "shortname": ":flag-py:",
            "code_decimal": "&#127477;&#127486;",
            "category": "f",
            "emoji_order": "2378"
        },
        {
            "name": "flag-qa",
            "unicode": "1f1f6-1f1e6",
            "shortname": ":flag-qa:",
            "code_decimal": "&#127478;&#127462;",
            "category": "f",
            "emoji_order": "2379"
        },
        {
            "name": "flag-re",
            "unicode": "1f1f7-1f1ea",
            "shortname": ":flag-re:",
            "code_decimal": "&#127479;&#127466;",
            "category": "f",
            "emoji_order": "2380"
        },
        {
            "name": "flag-ro",
            "unicode": "1f1f7-1f1f4",
            "shortname": ":flag-ro:",
            "code_decimal": "&#127479;&#127476;",
            "category": "f",
            "emoji_order": "2381"
        },
        {
            "name": "flag-rs",
            "unicode": "1f1f7-1f1f8",
            "shortname": ":flag-rs:",
            "code_decimal": "&#127479;&#127480;",
            "category": "f",
            "emoji_order": "2382"
        },
        {
            "name": "flag-ru",
            "unicode": "1f1f7-1f1fa",
            "shortname": ":flag-ru:",
            "code_decimal": "&#127479;&#127482;",
            "category": "f",
            "emoji_order": "2383"
        },
        {
            "name": "flag-rw",
            "unicode": "1f1f7-1f1fc",
            "shortname": ":flag-rw:",
            "code_decimal": "&#127479;&#127484;",
            "category": "f",
            "emoji_order": "2384"
        },
        {
            "name": "flag-sa",
            "unicode": "1f1f8-1f1e6",
            "shortname": ":flag-sa:",
            "code_decimal": "&#127480;&#127462;",
            "category": "f",
            "emoji_order": "2385"
        },


        {
            "name": "flag-sb",
            "unicode": "1f1f8-1f1e7",
            "shortname": ":flag-sb:",
            "code_decimal": "&#127480;&#127463;",
            "category": "f",
            "emoji_order": "2386"
        },
        {
            "name": "flag-sc",
            "unicode": "1f1f8-1f1e8",
            "shortname": ":flag-sc:",
            "code_decimal": "&#127480;&#127464;",
            "category": "f",
            "emoji_order": "2387"
        },
        {
            "name": "flag-sd",
            "unicode": "1f1f8-1f1e9",
            "shortname": ":flag-sd:",
            "code_decimal": "&#127480;&#127465;",
            "category": "f",
            "emoji_order": "2388"
        },
        {
            "name": "flag-se",
            "unicode": "1f1f8-1f1ea",
            "shortname": ":flag-se:",
            "code_decimal": "&#127480;&#127466;",
            "category": "f",
            "emoji_order": "2389"
        },
        {
            "name": "flag-sg",
            "unicode": "1f1f8-1f1ec",
            "shortname": ":flag-sg:",
            "code_decimal": "&#127480;&#127468;",
            "category": "f",
            "emoji_order": "2390"
        },
        {
            "name": "flag-sh",
            "unicode": "1f1f8-1f1ed",
            "shortname": ":flag-sh:",
            "code_decimal": "&#127480;&#127469;",
            "category": "f",
            "emoji_order": "2391"
        },
        {
            "name": "flag-si",
            "unicode": "1f1f8-1f1ee",
            "shortname": ":flag-si:",
            "code_decimal": "&#127480;&#127470;",
            "category": "f",
            "emoji_order": "2392"
        },
        {
            "name": "flag-sj",
            "unicode": "1f1f8-1f1ef",
            "shortname": ":flag-sj:",
            "code_decimal": "&#127480;&#127471;",
            "category": "f",
            "emoji_order": "2393"
        },
        {
            "name": "flag-sk",
            "unicode": "1f1f8-1f1f0",
            "shortname": ":flag-sk:",
            "code_decimal": "&#127480;&#127472;",
            "category": "f",
            "emoji_order": "2394"
        },
        {
            "name": "flag-sl",
            "unicode": "1f1f8-1f1f1",
            "shortname": ":flag-sl:",
            "code_decimal": "&#127480;&#127473;",
            "category": "f",
            "emoji_order": "2395"
        },
        {
            "name": "flag-sm",
            "unicode": "1f1f8-1f1f2",
            "shortname": ":flag-sm:",
            "code_decimal": "&#127480;&#127474;",
            "category": "f",
            "emoji_order": "2396"
        },
        {
            "name": "flag-sn",
            "unicode": "1f1f8-1f1f3",
            "shortname": ":flag-sn:",
            "code_decimal": "&#127480;&#127475;",
            "category": "f",
            "emoji_order": "2397"
        },
        {
            "name": "flag-so",
            "unicode": "1f1f8-1f1f4",
            "shortname": ":flag-so:",
            "code_decimal": "&#127480;&#127476;",
            "category": "f",
            "emoji_order": "2398"
        },
        {
            "name": "flag-sr",
            "unicode": "1f1f8-1f1f7",
            "shortname": ":flag-sr:",
            "code_decimal": "&#127480;&#127479;",
            "category": "f",
            "emoji_order": "2399"
        },
        {
            "name": "flag-ss",
            "unicode": "1f1f8-1f1f8",
            "shortname": ":flag-ss:",
            "code_decimal": "&#127480;&#127480;",
            "category": "f",
            "emoji_order": "2400"
        },
        {
            "name": "flag-st",
            "unicode": "1f1f8-1f1f9",
            "shortname": ":flag-st:",
            "code_decimal": "&#127480;&#127481;",
            "category": "f",
            "emoji_order": "2401"
        },
        {
            "name": "flag-sv",
            "unicode": "1f1f8-1f1fb",
            "shortname": ":flag-sv:",
            "code_decimal": "&#127480;&#127483;",
            "category": "f",
            "emoji_order": "2402"
        },
        {
            "name": "flag-sx",
            "unicode": "1f1f8-1f1fd",
            "shortname": ":flag-sx:",
            "code_decimal": "&#127480;&#127485;",
            "category": "f",
            "emoji_order": "2403"
        },
        {
            "name": "flag-sy",
            "unicode": "1f1f8-1f1fe",
            "shortname": ":flag-sy:",
            "code_decimal": "&#127480;&#127486;",
            "category": "f",
            "emoji_order": "2404"
        },
        {
            "name": "flag-sz",
            "unicode": "1f1f8-1f1ff",
            "shortname": ":flag-sz:",
            "code_decimal": "&#127480;&#127487;",
            "category": "f",
            "emoji_order": "2405"
        },
        {
            "name": "flag-ta",
            "unicode": "1f1f9-1f1e6",
            "shortname": ":flag-ta:",
            "code_decimal": "&#127481;&#127462;",
            "category": "f",
            "emoji_order": "2406"
        },
        {
            "name": "flag-tc",
            "unicode": "1f1f9-1f1e8",
            "shortname": ":flag-tc:",
            "code_decimal": "&#127481;&#127464;",
            "category": "f",
            "emoji_order": "2407"
        },
        {
            "name": "flag-td",
            "unicode": "1f1f9-1f1e9",
            "shortname": ":flag-td:",
            "code_decimal": "&#127481;&#127465;",
            "category": "f",
            "emoji_order": "2408"
        },
        {
            "name": "flag-tf",
            "unicode": "1f1f9-1f1eb",
            "shortname": ":flag-tf:",
            "code_decimal": "&#127481;&#127467;",
            "category": "f",
            "emoji_order": "2409"
        },
        {
            "name": "flag-tg",
            "unicode": "1f1f9-1f1ec",
            "shortname": ":flag-tg:",
            "code_decimal": "&#127481;&#127468;",
            "category": "f",
            "emoji_order": "2410"
        },
        {
            "name": "flag-th",
            "unicode": "1f1f9-1f1ed",
            "shortname": ":flag-th:",
            "code_decimal": "&#127481;&#127469;",
            "category": "f",
            "emoji_order": "2411"
        },
        {
            "name": "flag-tj",
            "unicode": "1f1f9-1f1ef",
            "shortname": ":flag-tj:",
            "code_decimal": "&#127481;&#127471;",
            "category": "f",
            "emoji_order": "2412"
        },
        {
            "name": "flag-tk",
            "unicode": "1f1f9-1f1f0",
            "shortname": ":flag-tk:",
            "code_decimal": "&#127481;&#127472;",
            "category": "f",
            "emoji_order": "2413"
        },
        {
            "name": "flag-tl",
            "unicode": "1f1f9-1f1f1",
            "shortname": ":flag-tl:",
            "code_decimal": "&#127481;&#127473;",
            "category": "f",
            "emoji_order": "2414"
        },
        {
            "name": "flag-tm",
            "unicode": "1f1f9-1f1f2",
            "shortname": ":flag-tm:",
            "code_decimal": "&#127481;&#127474;",
            "category": "f",
            "emoji_order": "2415"
        },
        {
            "name": "flag-tn",
            "unicode": "1f1f9-1f1f3",
            "shortname": ":flag-tn:",
            "code_decimal": "&#127481;&#127475;",
            "category": "f",
            "emoji_order": "2416"
        },
        {
            "name": "flag-to",
            "unicode": "1f1f9-1f1f4",
            "shortname": ":flag-to:",
            "code_decimal": "&#127481;&#127476;",
            "category": "f",
            "emoji_order": "2417"
        },
        {
            "name": "flag-tr",
            "unicode": "1f1f9-1f1f7",
            "shortname": ":flag-tr:",
            "code_decimal": "&#127481;&#127479;",
            "category": "f",
            "emoji_order": "2418"
        },
        {
            "name": "flag-tt",
            "unicode": "1f1f9-1f1f9",
            "shortname": ":flag-tt:",
            "code_decimal": "&#127481;&#127481;",
            "category": "f",
            "emoji_order": "2419"
        },
        {
            "name": "flag-tv",
            "unicode": "1f1f9-1f1fb",
            "shortname": ":flag-tv:",
            "code_decimal": "&#127481;&#127483;",
            "category": "f",
            "emoji_order": "2420"
        },
        {
            "name": "flag-tz",
            "unicode": "1f1f9-1f1ff",
            "shortname": ":flag-tz:",
            "code_decimal": "&#127481;&#127487;",
            "category": "f",
            "emoji_order": "2422"
        },
        {
            "name": "flag-ua",
            "unicode": "1f1fa-1f1e6",
            "shortname": ":flag-ua:",
            "code_decimal": "&#127482;&#127462;",
            "category": "f",
            "emoji_order": "2423"
        },
        {
            "name": "flag-ug",
            "unicode": "1f1fa-1f1ec",
            "shortname": ":flag-ug:",
            "code_decimal": "&#127482;&#127468;",
            "category": "f",
            "emoji_order": "2424"
        },
        {
            "name": "flag-um",
            "unicode": "1f1fa-1f1f2",
            "shortname": ":flag-um:",
            "code_decimal": "&#127482;&#127474;",
            "category": "f",
            "emoji_order": "2425"
        },
        {
            "name": "flag-us",
            "unicode": "1f1fa-1f1f8",
            "shortname": ":flag-us:",
            "code_decimal": "&#127482;&#127480;",
            "category": "f",
            "emoji_order": "2427"
        },
        {
            "name": "flag-uy",
            "unicode": "1f1fa-1f1fe",
            "shortname": ":flag-uy:",
            "code_decimal": "&#127482;&#127486;",
            "category": "f",
            "emoji_order": "2428"
        },
        {
            "name": "flag-uz",
            "unicode": "1f1fa-1f1ff",
            "shortname": ":flag-uz:",
            "code_decimal": "&#127482;&#127487;",
            "category": "f",
            "emoji_order": "2429"
        },
        {
            "name": "flag-va",
            "unicode": "1f1fb-1f1e6",
            "shortname": ":flag-va:",
            "code_decimal": "&#127483;&#127462;",
            "category": "f",
            "emoji_order": "2430"
        },
        {
            "name": "flag-vc",
            "unicode": "1f1fb-1f1e8",
            "shortname": ":flag-vc:",
            "code_decimal": "&#127483;&#127464;",
            "category": "f",
            "emoji_order": "2431"
        },
        {
            "name": "flag-ve",
            "unicode": "1f1fb-1f1ea",
            "shortname": ":flag-ve:",
            "code_decimal": "&#127483;&#127466;",
            "category": "f",
            "emoji_order": "2432"
        },
        {
            "name": "flag-vg",
            "unicode": "1f1fb-1f1ec",
            "shortname": ":flag-vg:",
            "code_decimal": "&#127483;&#127468;",
            "category": "f",
            "emoji_order": "2433"
        },
        {
            "name": "flag-vi",
            "unicode": "1f1fb-1f1ee",
            "shortname": ":flag-vi:",
            "code_decimal": "&#127483;&#127470;",
            "category": "f",
            "emoji_order": "2434"
        },
        {
            "name": "flag-vn",
            "unicode": "1f1fb-1f1f3",
            "shortname": ":flag-vn:",
            "code_decimal": "&#127483;&#127475;",
            "category": "f",
            "emoji_order": "2435"
        },

        {
            "name": "flag-vu",
            "unicode": "1f1fb-1f1fa",
            "shortname": ":flag_vu:",
            "code_decimal": "&#127483;&#127482;",
            "category": "f",
            "emoji_order": "2436"
        },
        {
            "name": "flag-wf",
            "unicode": "1f1fc-1f1eb",
            "shortname": ":flag_wf:",
            "code_decimal": "&#127484;&#127467;",
            "category": "f",
            "emoji_order": "2437"
        },
        {
            "name": "flag-ws",
            "unicode": "1f1fc-1f1f8",
            "shortname": ":flag_ws:",
            "code_decimal": "&#127484;&#127480;",
            "category": "f",
            "emoji_order": "2438"
        },
        {
            "name": "flag-xk",
            "unicode": "1f1fd-1f1f0",
            "shortname": ":flag_xk:",
            "code_decimal": "&#127485;&#127472;",
            "category": "f",
            "emoji_order": "2439"
        },
        {
            "name": "flag-ye",
            "unicode": "1f1fe-1f1ea",
            "shortname": ":flag_ye:",
            "code_decimal": "&#127486;&#127466;",
            "category": "f",
            "emoji_order": "2440"
        },
        {
            "name": "flag-yt",
            "unicode": "1f1fe-1f1f9",
            "shortname": ":flag_yt:",
            "code_decimal": "&#127486;&#127481;",
            "category": "f",
            "emoji_order": "2441"
        },
        {
            "name": "flag-za",
            "unicode": "1f1ff-1f1e6",
            "shortname": ":flag_za:",
            "code_decimal": "&#127487;&#127462;",
            "category": "f",
            "emoji_order": "2442"
        },
        {
            "name": "flag-zm",
            "unicode": "1f1ff-1f1f2",
            "shortname": ":flag_zm:",
            "code_decimal": "&#127487;&#127474;",
            "category": "f",
            "emoji_order": "2443"
        },
        {
            "name": "flag-zw",
            "unicode": "1f1ff-1f1fc",
            "shortname": ":flag_zw:",
            "code_decimal": "&#127487;&#127484;",
            "category": "f",
            "emoji_order": "2444"
        },
        {
            "name": "speech",
            "unicode": "1f600",
            "shortname": ":speech:",
            "code_decimal": "&#128172;",
            "category": "p",
            "emoji_order": "1"
        }
    ];

    if (typeof wind.emojiData === 'undefined') wind.emojiData = [];
    emojiData.forEach(function (item) {
        wind.emojiData.push(item)
    })
})(window)
