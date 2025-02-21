import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:uni_project/Features/Tickets/Presentation/Controllers/staff_ticket_details_controller.dart';

class StaffTicketDetailsScreen extends StatelessWidget {
  final StaffTicketDetailsController controller =
      Get.put(StaffTicketDetailsController());

  @override
  Widget build(BuildContext context) {
    final ticket = Get.arguments; // دریافت اطلاعات تیکت از صفحه قبل

    controller.loadMessages(
        ticket['id'], ticket['status'] == 'closed'); // بررسی وضعیت تیکت

    return Scaffold(
      appBar: AppBar(
        title:
            Text("تیکت: ${ticket['subject']}", style: TextStyle(fontSize: 18)),
        centerTitle: true,
      ),
      body: Column(
        children: [
          Expanded(
            child: Obx(() {
              if (controller.isLoading.value) {
                return Center(child: CircularProgressIndicator());
              }

              if (controller.messages.isEmpty) {
                return Center(child: Text("هیچ پیامی موجود نیست"));
              }

              return ListView.builder(
                itemCount: controller.messages.length,
                itemBuilder: (context, index) {
                  var message = controller.messages[index];
                  bool isStaff = message['sender_role'] == 'staff';

                  return Align(
                    alignment:
                        isStaff ? Alignment.centerRight : Alignment.centerLeft,
                    child: Container(
                      margin: EdgeInsets.symmetric(vertical: 4, horizontal: 8),
                      padding: EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: isStaff ? Colors.blue[200] : Colors.grey[300],
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(message['message']),
                    ),
                  );
                },
              );
            }),
          ),

          // فیلد وارد کردن پیام و دکمه ارسال (فقط اگر تیکت باز باشد)
          Obx(() {
            if (controller.isTicketClosed.value) {
              return Padding(
                padding: EdgeInsets.all(8.0),
                child: Text(
                  "🔒 این تیکت بسته شده است. امکان ارسال پیام وجود ندارد.",
                  style:
                      TextStyle(color: Colors.red, fontWeight: FontWeight.bold),
                ),
              );
            }

            return Padding(
              padding: EdgeInsets.all(8.0),
              child: Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: controller.messageController,
                      decoration: InputDecoration(
                        hintText: "پیام خود را وارد کنید...",
                        border: OutlineInputBorder(),
                      ),
                    ),
                  ),
                  IconButton(
                    icon: Icon(Icons.send),
                    onPressed: () => controller.sendMessage(ticket['id']),
                  ),
                ],
              ),
            );
          }),

          // دکمه بستن تیکت (فقط اگر تیکت باز باشد)
          if (ticket['status'] == 'open')
            Padding(
              padding: EdgeInsets.all(8.0),
              child: ElevatedButton(
                onPressed: () => controller.closeTicket(ticket['id']),
                child: Text("بستن تیکت"),
              ),
            ),
        ],
      ),
    );
  }
}
