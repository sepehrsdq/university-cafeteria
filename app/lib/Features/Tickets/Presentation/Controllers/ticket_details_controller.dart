import 'package:get/get.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

class TicketDetailsController extends GetxController {
  var messages = [].obs;
  var isLoading = false.obs;
  var isTicketClosed = false.obs;
  TextEditingController messageController = TextEditingController();
  int studentId = 0; // مقدار `student_id`

  @override
  void onInit() {
    super.onInit();
    loadStudentId();
  }

  // دریافت `student_id` از `shared_preferences`
  Future<void> loadStudentId() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    studentId = prefs.getInt('student_id') ?? 0;
  }

  // دریافت پیام‌های یک تیکت
  Future<void> loadMessages(int ticketId, bool isClosed) async {
    isLoading.value = true;
    isTicketClosed.value = isClosed; // بررسی وضعیت تیکت

    try {
      var response = await http.get(
        Uri.parse(
            "http://127.0.0.1/cafeteria_api/get_ticket_messages.php?ticket_id=$ticketId"),
      );

      var data = json.decode(response.body);
      if (data['success']) {
        messages.value = data['messages'];
      } else {
        Get.snackbar("خطا", "مشکلی در دریافت پیام‌ها وجود دارد");
      }
    } catch (e) {
      Get.snackbar("خطا", "اتصال به سرور برقرار نشد");
    } finally {
      isLoading.value = false;
    }
  }

  // ارسال پیام جدید
  Future<void> sendMessage(int ticketId) async {
    if (messageController.text.isEmpty || isTicketClosed.value) return;

    try {
      var response = await http.post(
        Uri.parse("http://127.0.0.1/cafeteria_api/send_message.php"),
        body: json.encode({
          "ticket_id": ticketId,
          "sender_id": studentId, // مقدار از `shared_preferences`
          "sender_role": "student",
          "message": messageController.text,
        }),
      );

      var data = json.decode(response.body);
      if (data['success']) {
        messages.add({
          "ticket_id": ticketId,
          "sender_id": studentId,
          "sender_role": "student",
          "message": messageController.text,
        });
        messageController.clear();
      } else {
        Get.snackbar(
            "خطا", "مشکلی در ارسال پیام وجود دارد: ${data['message']}");
      }
    } catch (e) {
      print(e);
    }
  }
}
