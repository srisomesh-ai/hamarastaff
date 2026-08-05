package com.hamarastaff.app;

import android.webkit.WebView;
import com.google.firebase.messaging.FirebaseMessaging;

public class PushBridge {
    public static void init(final MainActivity act, final WebView web) {
        FirebaseMessaging.getInstance().getToken().addOnSuccessListener(token -> {
            if (token == null) return;
            act.getSharedPreferences("hs", MainActivity.MODE_PRIVATE)
               .edit().putString("fcm_token", token).apply();
            act.runOnUiThread(() -> web.evaluateJavascript(
                "try{localStorage.setItem('hs_push','" + token + "');" +
                "window.hsSetPushToken&&hsSetPushToken('" + token + "');}catch(e){}", null));
        });
    }
}
