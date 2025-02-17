import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:uni_project/Features/Dinner/Presentation/Controllers/dinner_controller.dart';
import 'package:shamsi_date/shamsi_date.dart';
import 'package:uni_project/Features/MainPage/Presentation/Controllers/main_page_controller.dart';

class DinnerScreen extends StatelessWidget {
  final DinnerController controller = Get.put(DinnerController());

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text("Self Plus"),
        centerTitle: true,
        leading: IconButton(
          onPressed: controller.routeMainPage,
          icon: const Icon(Icons.keyboard_backspace),
        ),
      ),
      body: Obx(() {
        if (controller.isLoading.value) {
          return const Center(child: CircularProgressIndicator());
        }

        return ListView.builder(
          physics: const AlwaysScrollableScrollPhysics(),
          itemCount: controller.dinners.length,
          itemBuilder: (context, index) {
            final item = controller.dinners[index];

            return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Padding(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  child: Row(
                    children: [
                      CircleAvatar(
                        radius: 24,
                        backgroundColor: Colors.grey[300],
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Text(
                              _convertToShamsiDay(item["date"]),
                              style: const TextStyle(
                                  fontSize: 18, fontWeight: FontWeight.bold),
                            ),
                            Text(
                              _convertToShamsiMonth(item["date"]),
                              style: const TextStyle(fontSize: 12),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: InkWell(
                          onTap: () => _showReservationDialog(item["id"]),
                          child: Padding(
                            padding: const EdgeInsets.only(bottom: 12),
                            child: Row(
                              crossAxisAlignment: CrossAxisAlignment.center,
                              children: [
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment:
                                        CrossAxisAlignment.start,
                                    children: [
                                      const Text("19:00 - 21:00",
                                          style: TextStyle(
                                              fontSize: 12,
                                              color: Colors.grey)),
                                      Text(item["name"],
                                          style: const TextStyle(
                                              fontSize: 16,
                                              fontWeight: FontWeight.bold)),
                                      const SizedBox(height: 8),
                                      Row(
                                        children: [
                                          Container(
                                            padding: const EdgeInsets.symmetric(
                                                horizontal: 8, vertical: 4),
                                            decoration: BoxDecoration(
                                              color: Colors.blue[200],
                                              borderRadius:
                                                  BorderRadius.circular(12),
                                            ),
                                            child: Text(
                                              "${item["price"]} تومان",
                                              style: const TextStyle(
                                                  fontSize: 12,
                                                  color: Colors.white),
                                            ),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                                const SizedBox(width: 12),
                                ClipRRect(
                                  borderRadius: BorderRadius.circular(8),
                                  child: Image.network(
                                    item["image_url"],
                                    width: 70,
                                    height: 70,
                                    fit: BoxFit.cover,
                                    errorBuilder: (context, error, stackTrace) {
                                      return const Icon(
                                          Icons.image_not_supported,
                                          size: 70,
                                          color: Colors.grey);
                                    },
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                Divider(color: Colors.grey[300]),
              ],
            );
          },
        );
      }),
    );
  }

  void _showReservationDialog(int mealId) {
    Get.defaultDialog(
      title: "تایید رزرو",
      middleText: "آیا از رزرو خود مطمئن هستید؟",
      confirm: ElevatedButton(
        onPressed: () async {
          String result = await controller.reserveMeal(mealId);

          Get.back(); // بستن دیالوگ
          if (result == "success") {
            final mainPageController = Get.put(MainPageController());
            await mainPageController.fetchBalance();
            controller.routeMainPage();
            Get.snackbar(
              "موفقیت‌آمیز 🎉",
              "غذا با موفقیت رزرو شد!",
              snackPosition: SnackPosition.BOTTOM,
              backgroundColor: Colors.green,
              colorText: Colors.white,
            );
          } else {
            Get.snackbar(
              "خطا ❌",
              result,
              snackPosition: SnackPosition.BOTTOM,
              backgroundColor: Colors.red,
              colorText: Colors.white,
            );
          }
        },
        child: const Text("بله"),
      ),
      cancel: ElevatedButton(
        onPressed: () {
          Get.back();
        },
        child: const Text("خیر"),
      ),
    );
  }
}

// تبدیل تاریخ میلادی به شمسی
String _convertToShamsiDay(String date) {
  DateTime dateTime = DateTime.parse(date);
  Jalali jalaliDate = Jalali.fromDateTime(dateTime);
  return jalaliDate.day.toString();
}

String _convertToShamsiMonth(String date) {
  DateTime dateTime = DateTime.parse(date);
  Jalali jalaliDate = Jalali.fromDateTime(dateTime);
  List<String> months = [
    "فروردین",
    "اردیبهشت",
    "خرداد",
    "تیر",
    "مرداد",
    "شهریور",
    "مهر",
    "آبان",
    "آذر",
    "دی",
    "بهمن",
    "اسفند"
  ];
  return months[jalaliDate.month - 1];
}
