const skinOptionsList = [
    {
        lable: "默认",
        key: "white",
    },
    {
        lable: "暗黑主题",
        key: "black",
    },
];

const calendarColorList={
    white:
        {
            'common.border': '1px solid #f4f5f5',
            'common.holiday.color': '#333',
            'common.saturday.color': '#333',
            'common.dayname.color': '#333',
            'common.backgroundColor':'#ffffff',
         
        },
    
    black: 
        {
            'common.border': '1px solid #ffffff',
            'common.holiday.color': '#ffffff',
            'common.saturday.color': '#ffffff',
            'common.dayname.color': '#ffffff',
            'common.backgroundColor':'#171717',
            'common.creationGuide.color': '#ffffff',
            'common.creationGuide.color': '#ffffff',
            'common.today.color': '#ffffff',
        },
}


export { skinOptionsList,calendarColorList };
