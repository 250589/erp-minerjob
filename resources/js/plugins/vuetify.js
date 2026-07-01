import 'vuetify/styles';
import '@mdi/font/css/materialdesignicons.css';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';

export default createVuetify({
    components,
    directives,
    icons: {
        defaultSet: 'mdi',
    },
    theme: {
        defaultTheme: 'light',
        themes: {
            light: {
                colors: {
                    primary: '#0C447C',
                    secondary: '#534AB7',
                    success: '#3B6D11',
                    warning: '#854F0B',
                    error: '#A32D2D',
                },
            },
        },
    },
});
