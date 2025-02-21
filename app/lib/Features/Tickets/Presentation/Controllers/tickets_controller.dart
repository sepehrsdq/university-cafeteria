import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class TicketsController extends GetxController {
  var tickets = <Map<String, dynamic>>[].obs;
  var isLoading = true.obs;

  @override
  void onInit() {
    fetchTickets();
    super.onInit();
  }

  Future<void> fetchTickets() async {
    try {
      isLoading(true);
      SharedPreferences prefs = await SharedPreferences.getInstance();
      int studentId = prefs.getInt('student_id') ?? 0;

      if (studentId == 0) {
        isLoading(false);
        return;
      }

      var response = await http.get(
        Uri.parse(
            "http://127.0.0.1/cafeteria_api/get_tickets.php?user_id=$studentId&role=student"),
      );

      var data = jsonDecode(response.body);
      if (data['success']) {
        tickets.assignAll(List<Map<String, dynamic>>.from(data['tickets']));
      }
    } catch (e) {
      print("Error fetching tickets: $e");
    } finally {
      isLoading(false);
    }
  }
}
