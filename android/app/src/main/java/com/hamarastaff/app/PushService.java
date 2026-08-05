package com.hamarastaff.app;

import android.app.Notification;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.Intent;
import android.os.Build;

import androidx.core.app.NotificationCompat;

import com.google.firebase.messaging.FirebaseMessagingService;
import com.google.firebase.messaging.RemoteMessage;

public class PushService extends FirebaseMessagingService {

    public static final String CHANNEL_ID = "hamarastaff";

    @Override
    public void onMessageReceived(RemoteMessage msg) {
        String title = "HamaraStaff";
        String body = "";
        if (msg.getNotification() != null) {
            if (msg.getNotification().getTitle() != null) title = msg.getNotification().getTitle();
            if (msg.getNotification().getBody() != null) body = msg.getNotification().getBody();
        }
        NotificationManager nm = (NotificationManager) getSystemService(NOTIFICATION_SERVICE);
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            nm.createNotificationChannel(new NotificationChannel(CHANNEL_ID, "HamaraStaff", NotificationManager.IMPORTANCE_HIGH));
        }
        Intent open = new Intent(this, MainActivity.class);
        open.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TOP);
        PendingIntent pi = PendingIntent.getActivity(this, 0, open,
                PendingIntent.FLAG_UPDATE_CURRENT | PendingIntent.FLAG_IMMUTABLE);
        Notification n = new NotificationCompat.Builder(this, CHANNEL_ID)
                .setSmallIcon(R.mipmap.ic_launcher)
                .setContentTitle(title)
                .setContentText(body)
                .setStyle(new NotificationCompat.BigTextStyle().bigText(body))
                .setAutoCancel(true)
                .setPriority(NotificationCompat.PRIORITY_HIGH)
                .setContentIntent(pi)
                .build();
        nm.notify((int) (System.currentTimeMillis() % 100000), n);
    }

    @Override
    public void onNewToken(String token) {
        getSharedPreferences("hs", MODE_PRIVATE).edit().putString("fcm_token", token).apply();
    }
}
