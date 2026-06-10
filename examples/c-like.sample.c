struct Point {
    int x;
    int y;
};

int add(int left, int right) {
    return left + right + 1 + 2 + 3 + 4;
}



int main() {
    int total = add(1, 2);

    while (total < 10) {
        total = total + 1;
    }

    return total;
}
