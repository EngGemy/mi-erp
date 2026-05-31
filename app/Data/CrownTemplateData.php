<?php

namespace App\Data;

/**
 * بيانات قالب كراون — أقسام وأصناف ومعادلات (مطابق لملف الإكسل).
 * يُستخدم من CatalogSeeder (مصدر البيانات الأولي للكتالوج المركزي).
 */
class CrownTemplateData
{
    /**
     * @return list<string>
     */
    public static function sectionNames(): array
    {
        return [
            'هيكل رئيسي',
            'مقدمات سبلة',
            'نهايات سبله',
            'منظومة علف',
            'سير عرضي',
            'منظومه خطوط مياه',
            'سير بطارية',
            'لوازم تركيب',
            'كنترول',
            'مواتير',
        ];
    }

    /**
     * @return list<array{0: string, 1: string, 2: string, 3: float|null, 4: string, 5: array}>
     *         [code, name, section, piece_length, formula, scrap]
     *         scrap: ['inherit'] | ['percent', x] | ['fixed', n] | ['none']
     */
    public static function items(): array
    {
        return [
            ['leg_post', 'رجل قايم تسمين', 'هيكل رئيسي', null, '(((cages+2)*2)*lines)', ['percent', 1]],
            ['araweh', 'عروة رجلاش', 'هيكل رئيسي', null, "item('leg_post')+(lines*4)", ['percent', 5]],
            ['top_link_3m', 'رابط علوي 3 متر', 'هيكل رئيسي', 3, '((cages+2)/3)*lines*2', ['percent', 1]],
            ['omega_3m', 'اوميجا 3 متر', 'هيكل رئيسي', 3, '((cages)/3)*2*tiers*lines', ['percent', 1]],
            ['omega_1m', 'اوميجا 1متر', 'هيكل رئيسي', 1, '(tiers*2)*lines', ['percent', 25]],
            ['wire_side_holder', 'حامل جنب السلك', 'هيكل رئيسي', null, '(cages+1)*tiers*lines', ['percent', 1]],
            ['belt_holder', 'حامل سير سبلة (تخريج ألي - 3 تحت كل عش)', 'هيكل رئيسي', null, '((cages+1)*3)*tiers*lines', ['percent', 1]],
            ['belt_holder_low', 'حامل سير سبلة سفلى', 'هيكل رئيسي', null, "item('leg_post')/2", ['percent', 1]],
            ['feeder_3m', 'علافات 3 متر', 'هيكل رئيسي', 3, '(cages+1)/3*2*lines*tiers', ['percent', 1]],
            ['feeder_08m', 'علافات 0.8 متر', 'هيكل رئيسي', 0.8, 'lines*tiers*2', ['percent', 2]],
            ['feeder_holder', 'حامل علافة', 'هيكل رئيسي', null, "(item('leg_post')+(lines*2))*tiers", ['percent', 1]],
            ['saddat', 'صدادات', 'هيكل رئيسي', null, 'cages*lines*tiers*2', ['percent', 1]],
            ['feeder_curtain_3m', 'ستارة علافة 3 متر', 'هيكل رئيسي', null, '((cages+1)/3)*2*tiers*lines', ['percent', 1]],
            ['floor_holder', 'حامل ارضيه بلاستيك', 'هيكل رئيسي', null, '(cages+1)*tiers*lines', ['percent', 1]],
            ['feeder_curtain_holder', 'حامل ستارة علافه', 'هيكل رئيسي', null, "item('leg_post')*tiers", ['percent', 1]],
            ['water_pipe_slot', 'سلوت شداد ماسورة مياه', 'هيكل رئيسي', null, "item('water_pipe_tight')", ['percent', 1]],
            ['water_pipe_tight', 'شداد ماسورة مياه', 'هيكل رئيسي', 3, '((cages+1)/3)*2*tiers*lines', ['percent', 1]],
            ['water_down', 'نزلات ميا', 'هيكل رئيسي', null, "item('floor_plastic')", ['percent', 1]],
            ['water_pipe_clip', 'كلبش ماسورة مياه (93 في الكيلو)', 'هيكل رئيسي', null, '((cages+1)*tiers*lines)*4', ['percent', 1]],
            ['feeder_cap', 'طبه علافات شمال ويمين', 'هيكل رئيسي', null, 'lines*tiers*2', ['percent', 1]],
            ['wire_side', 'جنب سلك تسمين', 'هيكل رئيسي', null, '(cages+1)*tiers*lines', ['percent', 1]],
            ['wire_face', 'وش سلك', 'هيكل رئيسي', null, '((cages/2)*tiers*2*lines)*2', ['percent', 1]],
            ['wire_sep', 'فاصل السلك تسمين', 'هيكل رئيسي', null, 'cages*lines*tiers', ['percent', 1]],
            ['cage_door_dsr', 'باب العش دسرة', 'هيكل رئيسي', null, '(cages*lines*tiers)/2*2', ['percent', 1]],
            ['cage_door_nodsr', 'باب العش بدون دسرة', 'هيكل رئيسي', null, '(cages*lines*tiers)/2*2', ['percent', 1]],
            ['roof_2x125', 'سقف 2 متر * 1.25 متر', 'هيكل رئيسي', 3, '((cages)/2)*lines', ['percent', 1]],
            ['floor_plastic', 'ارضيه بلاستيك تسمين', 'هيكل رئيسي', null, 'cages*lines*tiers*2', ['percent', 1]],
            ['floor_support', 'دعامه حامل ارضيه (كبيره-صغيره)', 'هيكل رئيسي', null, "item('floor_plastic')", ['percent', 1]],
            ['door_wire_long', 'سلك طولي حامل أبواب', 'هيكل رئيسي', null, 'cages*lines*tiers*2*0.045', ['none']],
            ['bolt_leg', 'مسمار رجلاش طول 20 سم 12 مم مسدس قاعدة بكليت', 'هيكل رئيسي', null, "item('araweh')+10", ['fixed', 9]],
            ['bolt_araweh', 'مسمار تجميع عروة رجلاش (طاسة 6 مم) 12/16مم', 'هيكل رئيسي', null, "itemF('bolt_leg')*2+100", ['percent', 5]],
            ['bolt_omega', 'مسمار تجميع اوميجا (طاسه 6 مم) 12/16مم', 'هيكل رئيسي', null, "itemF('omega_3m')*8", ['percent', 5]],
            ['bolt_top_link', 'مسامير تجميع رابط علوي (طاسه 6 مم) 16مم', 'هيكل رئيسي', null, "itemF('top_link_3m')*6", ['percent', 5]],
            ['bolt_feeder', 'مسامير تجميع علافات (مسدس 6 مم) 16مم', 'هيكل رئيسي', null, "itemF('feeder_holder')", ['percent', 5]],
            ['bolt_curtain_80', 'مسمار تجميع ستارة العلف (6مم طاسة 80مم)', 'هيكل رئيسي', null, "item('leg_post')*tiers", ['percent', 1]],
            ['bolt_curtain_100', 'مسمار تجميع ستارة العلف (6مم طاسة 100مم)', 'هيكل رئيسي', null, "item('feeder_curtain_3m')*2", ['percent', 5]],
            ['bolt_clip', 'مسمار كلبش ماسورة مياه (6مم طاسة 12مم)', 'هيكل رئيسي', null, "item('water_pipe_clip')/2", ['percent', 5]],

            ['front_line', 'مقدمه تسمين (للخط الواحد)', 'مقدمات سبلة', null, 'lines', ['none']],
            ['cabinet_line', 'دولاب سبلة تسمين (للخط الواحد)', 'نهايات سبله', null, 'lines', ['none']],
            ['feed_car', 'عربية علف للجنب الواحد', 'منظومة علف', 4, 'lines*2', ['none']],
            ['feed_chassis', 'شاسيه عربيات علف (للخط الواحد)', 'منظومة علف', null, 'lines', ['none']],
            ['feed_wire', 'واير عربيات علف 6مم مكسي بلاستيك الماني', 'منظومة علف', null, 'lines', ['none']],
            ['wire_lock', 'قفل وير 6 مللى', 'منظومة علف', null, 'lines*2', ['percent', 20]],
            ['screw_pipe_plastic', 'ماسورة بريمه بلاستيك 3 بوصه 90مم طول 2.7م', 'منظومة علف', null, 'lines+2', ['none']],
            ['elbow_half', 'كوع نصف دائري بريمه علف 3 بوصه 90مم', 'منظومة علف', null, '2', ['fixed', 1]],
            ['elbow_t', 'كوع T بلاستيك 3 بوصه 90مم', 'منظومة علف', null, 'lines*2+1', ['none']],
            ['screw_spring', 'سوسته بريمه علف 4 بوصه', 'منظومة علف', null, '1', ['none']],
            ['screw_base', 'قاعدة ماتور بريمة داخلية', 'منظومة علف', null, '1', ['none']],

            ['cross_chassis', 'شاسيه سير سبله عرضي 4 متر (4 خط)', 'سير عرضي', 4, 'lines+2', ['none']],
            ['cross_belt_5', 'سير سبله عراضي كاوتش 60سم 2تيله شيفرون داخلي 5خط', 'سير عرضي', null, '1', ['none']],
            ['cross_belt_out', 'سير سبله عراضي كاوتش 60سم 2تيله شيفرون خارجي', 'سير عرضي', null, '1', ['none']],
            ['cross_group', 'مجموعه سير عرضي', 'سير عرضي', null, '2', ['none']],
            ['cross_wipers', 'مساحات جانبيه سير عرضي سبله', 'سير عرضي', null, '18', ['none']],

            ['water_pipe_sq', 'مواسير مياه مربعه 22مم (تخانه 3مم بدوسرة) 12 نبل', 'منظومه خطوط مياه', null, '(cages/4)*2*tiers*lines', ['none']],
            ['nipple', 'نبل استانلس 360 درجه', 'منظومه خطوط مياه', null, 'cages*2*tiers*lines*3', ['percent', 2]],
            ['cup_plastic', 'كب بلاستيك افيز', 'منظومه خطوط مياه', null, 'cages*2*tiers*lines*3', ['percent', 1]],
            ['water_conn_sil', 'وصلة ماسورة مياه سليكون', 'منظومه خطوط مياه', null, "item('water_pipe_sq')", ['percent', 2]],
            ['water_corner', 'وصله مربع زاويه 90 مقدمات ونهايات', 'منظومه خطوط مياه', null, 'lines*tiers*4', ['fixed', 5]],
            ['hose_roll', 'لفه خرطوم 3/4 بوصه سيلكون ناشف', 'منظومه خطوط مياه', null, '2', ['none']],
            ['siphon', 'سيفون مياه تركي', 'منظومه خطوط مياه', null, 'lines*tiers', ['none']],
            ['hot_glue', 'علبه لزق حراري نص كيلو 904 امريكي', 'منظومه خطوط مياه', null, '8', ['none']],

            ['battery_belt', 'سيور سبله عرض 118سم تخانه 1مم pp', 'سير بطارية', null, 'lines*tiers', ['none']],

            ['tie_wire', 'سلك رباط او افيز تجميع سقف سلك (الكيس 100 قطعه)', 'لوازم تركيب', null, 'cages*lines*2', ['fixed', 100]],

            ['control_board', 'لوح كنترول', 'كنترول', null, '1', ['none']],
            ['cables', 'كابلات كهرباء متنوعه', 'كنترول', null, '1', ['none']],
            ['cable_trays', 'تريهات كابلات', 'كنترول', null, '1', ['none']],
            ['feed_sensors', 'حساسات عربيات علف', 'كنترول', null, 'lines*2', ['none']],

            ['motor_cabinet', 'ماتور دولاب سبله', 'مواتير', null, 'tiers*1*lines', ['none']],
            ['motor_cross', 'ماتور سير عرضي', 'مواتير', null, "item('cross_group')", ['none']],
            ['motor_silo', 'ماتور بريمه سايلو خارجيه', 'مواتير', null, '0', ['none']],
            ['motor_inner', 'ماتور بريمه داخليه', 'مواتير', null, '1', ['none']],
            ['motor_feed', 'ماتور علف', 'مواتير', null, "item('cabinet_line')", ['none']],

            ['pulley_v', 'قاعده بكر V سير سبله عرضي', 'سير عرضي', null, "itemF('cross_chassis')*3", ['none']],
            ['pulley_return', 'بكره راجع سير سبله عرضي', 'سير عرضي', null, "itemF('cross_chassis')*2", ['none']],
            ['chain_cross_b10', 'كتينه سير سبله عراضي B10 (لفه 5 متر)', 'سير عرضي', null, '1', ['none']],
            ['wipers_steel', 'مساحات استانلس سير عرضي', 'سير عرضي', null, '2', ['none']],
            ['chain_cabinet_b10', 'كتينه دولاب السبلة B10', 'سير عرضي', null, 'lines*1.25', ['none']],
            ['chain_front_b8', 'كتينه شداد مقدمه سيورسبلة B8', 'سير عرضي', null, '(lines*tiers*2)/5', ['fixed', 0.6]],
        ];
    }
}
