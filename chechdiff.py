import re


def compare_arrays(file1, file2, output_file):

    with open(file1, 'r') as f1:
        next(f1)
        content1 = f1.read()


    with open(file2, 'r') as f2:
        next(f2)
        content2 = f2.read()

    regex_pattern = r"(.*?)"
    array1 = re.findall(regex_pattern, content1)
    array2 = re.findall(regex_pattern, content2)
    print(array1)
    print(array2)

    differences = []
    for i in range(len(array1)):
        if array1[i] != array2[i]:
            differences.append((array1[i], array2[i]))

    with open(output_file, 'w') as f_out:
        for diff in differences:
            f_out.write(f'{diff[0]} != {diff[1]}\n')


compare_arrays('log_c1.txt', 'log_c2.txt', 'differences.txt')
